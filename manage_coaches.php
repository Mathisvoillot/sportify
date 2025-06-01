<?php
require_once 'config.php';

// verifier que l’utilisateur est co et a le role admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    exit('Accès refusé. Vous devez être administrateur.');
}

// fonctions utilitaires
function jourEnTexte($num) {
    $jours = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche'
    ];
    return $jours[$num] ?? 'Inconnu';
}

$errorCoach = '';
$successCoach = '';
$errorAvail = '';
$successAvail = '';

// traitement du formulaire ajouter un coach
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_coach') {
    // recuperation et nettoyage des champs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $specialty  = trim($_POST['specialty'] ?? '');

    // validation basique
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($specialty)) {
        $errorCoach = 'Veuillez remplir tous les champs pour le coach.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorCoach = 'Adresse email invalide.';
    } else {
        // verifier si l’email est deja utilise
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errorCoach = 'Cette adresse email est déjà utilisée.';
        } else {
            // inserer dans users
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, role)
                VALUES (:email, :password_hash, :first_name, :last_name, 'coach')
            ");
            try {
                $stmt2->execute([
                    ':email'         => $email,
                    ':password_hash' => $password_hash,
                    ':first_name'    => $first_name,
                    ':last_name'     => $last_name
                ]);
                $newUserId = $pdo->lastInsertId();

                // inserer dans coaches
                $stmt3 = $pdo->prepare("
                    INSERT INTO coaches (id_user, specialty)
                    VALUES (:id_user, :specialty)
                ");
                $stmt3->execute([
                    ':id_user'   => $newUserId,
                    ':specialty' => $specialty
                ]);

                $successCoach = 'Coach ajouté avec succès !';
            } catch (PDOException $e) {
                $errorCoach = 'Erreur lors de l\'ajout du coach : ' . $e->getMessage();
            }
        }
    }
}

// traitement du formulaire ajouter une disponibilite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_avail') {
    // recuperation et nettoyage des champs
    $coach_id    = (int) ($_POST['coach_id'] ?? 0);
    $day_of_week = (int) ($_POST['day_of_week'] ?? 0);
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';

    // validation basique
    if ($coach_id <= 0 || $day_of_week < 1 || $day_of_week > 7 || empty($start_time) || empty($end_time)) {
        $errorAvail = 'Veuillez sélectionner un coach, un jour et des horaires valides.';
    } elseif (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $start_time) || !preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $end_time)) {
        $errorAvail = 'Format d\'horaire invalide. Utilisez HH:MM.';
    } elseif ($start_time >= $end_time) {
        $errorAvail = 'L\'heure de début doit être antérieure à l\'heure de fin.';
    } else {
        // verifier l existence du coach
        $stmtCheckCoach = $pdo->prepare("SELECT COUNT(*) AS count FROM coaches WHERE id_coach = ?");
        $stmtCheckCoach->execute([$coach_id]);
        $rowCheckCoach = $stmtCheckCoach->fetch();
        if (!$rowCheckCoach || $rowCheckCoach['count'] == 0) {
            $errorAvail = 'Coach sélectionné introuvable.';
        } else {
            // inserer la dispo
            $stmt4 = $pdo->prepare("
                INSERT INTO availabilities (id_coach, day_of_week, start_time, end_time, is_available)
                VALUES (:id_coach, :day_of_week, :start_time, :end_time, 1)
            ");
            try {
                $stmt4->execute([
                    ':id_coach'    => $coach_id,
                    ':day_of_week' => $day_of_week,
                    ':start_time'  => $start_time . ':00', // converti en HH:MM:00
                    ':end_time'    => $end_time . ':00'
                ]);
                $successAvail = 'Disponibilité ajoutée avec succès !';
            } catch (PDOException $e) {
                $errorAvail = 'Erreur lors de l\'ajout de la disponibilité : ' . $e->getMessage();
            }
        }
    }
}

// recuperer la liste des coachs 
$stmtListCoachs = $pdo->query("
    SELECT c.id_coach, u.first_name, u.last_name, c.specialty
    FROM coaches AS c
    JOIN users AS u ON c.id_user = u.id_user
    ORDER BY u.last_name ASC
");
$coachsList = $stmtListCoachs->fetchAll();

require_once 'header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
    <section class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Ajouter un nouveau coach</h2>
        <?php if ($errorCoach): ?>
            <div class="bg-red-100 text-red-700 border border-red-400 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($errorCoach) ?>
            </div>
        <?php endif; ?>
        <?php if ($successCoach): ?>
            <div class="bg-green-100 text-green-700 border border-green-400 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($successCoach) ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_coach">
            <div>
                <label for="first_name" class="block text-gray-700 font-medium mb-2">Prénom</label>
                <input type="text" id="first_name" name="first_name" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>
            <div>
                <label for="last_name" class="block text-gray-700 font-medium mb-2">Nom</label>
                <input type="text" id="last_name" name="last_name" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" id="email" name="email" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div>     
                <label for="password" class="block text-gray-700 font-medium mb-2">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="specialty" class="block text-gray-700 font-medium mb-2">Spécialité</label>
                <input type="text" id="specialty" name="specialty" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="<?= htmlspecialchars($_POST['specialty'] ?? '') ?>">
            </div> 
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">Ajouter le coach</button>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Ajouter une disponibilité</h2>
        <?php if ($errorAvail): ?>
            <div class="bg-red-100 text-red-700 border border-red-400 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($errorAvail) ?>
            </div>
        <?php endif; ?>
        <?php if ($successAvail): ?>
            <div class="bg-green-100 text-green-700 border border-green-400 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($successAvail) ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_avail">
            <div>
                <label for="coach_id" class="block text-gray-700 font-medium mb-2">Coach</label>
                <select id="coach_id" name="coach_id" required
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Sélectionnez un coach --</option>
                    <?php foreach ($coachsList as $c): ?>
                        <option value="<?= $c['id_coach'] ?>" <?= ((int)($_POST['coach_id'] ?? 0) === (int)$c['id_coach']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialty'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="day_of_week" class="block text-gray-700 font-medium mb-2">Jour de la semaine</label>
                <select id="day_of_week" name="day_of_week" required
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Sélectionnez un jour --</option>
                    <?php for ($d = 1; $d <= 7; $d++): ?>
                        <option value="<?= $d ?>" <?= ((int)($_POST['day_of_week'] ?? 0) === $d) ? 'selected' : '' ?>>
                            <?= jourEnTexte($d) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-gray-700 font-medium mb-2">Heure de début</label>
                    <input type="time" id="start_time" name="start_time" required
                           class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>">
                </div>
                <div>
                    <label for="end_time" class="block text-gray-700 font-medium mb-2">Heure de fin</label>
                    <input type="time" id="end_time" name="end_time" required
                           class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">Ajouter la disponibilité</button>
        </form>

        <?php if (!empty($coachsList)): ?>
            <hr class="my-6 border-gray-300">

            <?php
            // afficher par coach leurs dispo  existantes
            // recuperer toutes les disponibilites avec les info du coach
            $stmtAllAvail = $pdo->query("
                SELECT a.id_avail, c.id_coach, u.first_name, u.last_name, c.specialty,
                       a.day_of_week, a.start_time, a.end_time, a.is_available
                FROM availabilities AS a
                JOIN coaches AS c ON a.id_coach = c.id_coach
                JOIN users AS u ON c.id_user = u.id_user
                ORDER BY u.last_name, a.day_of_week, a.start_time
            ");
            $allAvail = $stmtAllAvail->fetchAll();
            ?>

            <h3 class="text-xl font-bold mb-4">Liste des disponibilités existantes</h3>
            <?php if (empty($allAvail)): ?>
                <p class="text-gray-500">Aucune disponibilité enregistrée.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left">Coach</th>
                                <th class="px-4 py-2 text-left">Spécialité</th>
                                <th class="px-4 py-2 text-left">Jour</th>
                                <th class="px-4 py-2 text-left">De</th>
                                <th class="px-4 py-2 text-left">À</th>
                                <th class="px-4 py-2 text-left">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allAvail as $a): ?>
                                <tr class="<?= $a['is_available'] ? '' : 'bg-gray-100' ?>">
                                    <td class="border px-4 py-2"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                    <td class="border px-4 py-2"><?= htmlspecialchars($a['specialty']) ?></td>
                                    <td class="border px-4 py-2"><?= jourEnTexte($a['day_of_week']) ?></td>
                                    <td class="border px-4 py-2"><?= substr($a['start_time'], 0, 5) ?></td>
                                    <td class="border px-4 py-2"><?= substr($a['end_time'], 0, 5) ?></td>
                                    <td class="border px-4 py-2">
                                        <?= $a['is_available'] ? '<span class="text-green-600 font-medium">Disponible</span>' : '<span class="text-red-600 font-medium">Indisponible</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?phprequire_once 'footer.php';?>
