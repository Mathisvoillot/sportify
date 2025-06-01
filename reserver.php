<?php
// ===================== reserver.php =====================
// Multi-étapes : choix du coach/date → choix du créneau → confirmation

require_once 'config.php';

// 1) Vérifier que l’utilisateur est connecté ET a le rôle "client"
if (!isLoggedIn() || getUserRole() !== 'client') {
    // Si l’utilisateur n’est pas client, le rediriger vers la page de connexion
    header('Location: login.php');
    exit();
}

// 2) Récupérer la liste des coachs pour le <select>
$stmtCoachs = $pdo->query("
    SELECT c.id_coach, u.first_name, u.last_name, c.specialty
    FROM coaches AS c
    JOIN users AS u ON c.id_user = u.id_user
    ORDER BY u.last_name, u.first_name
");
$coachsList = $stmtCoachs->fetchAll();

// Variable pour stocker les messages d’erreur / succès
$error = '';
$success = '';

// 3) Déterminer l’étape actuelle du formulaire
//    Par défaut, on est à l’étape “choose_coach_date”
$step = $_POST['step'] ?? 'choose_coach_date';

// 4) Selon l’étape, traiter le POST ou préparer les données pour l’affichage
// -----------------------------------------------
//  Étape 1 : Choisir le coach et la date
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'choose_coach_date') {
    $coach_id = (int) ($_POST['coach_id'] ?? 0);
    $date      = trim($_POST['date'] ?? '');

    // Validation basique : coach_id valide et date non vide
    if ($coach_id <= 0 || empty($date)) {
        $error = 'Veuillez sélectionner un coach et une date valides.';
    } else {
        // Vérifier que la date est au format YYYY-MM-DD et pas antérieure à aujourd’hui
        $today = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $today) {
            $error = 'La date choisie est invalide ou antérieure à aujourd’hui.';
        } else {
            // Calculer le jour de la semaine (1=lundi, …, 7=dimanche)
            $day_of_week = (int) date('N', strtotime($date));

            // 4.a) Récupérer les disponibilités du coach pour ce jour de la semaine
            $stmtAvail = $pdo->prepare("
                SELECT a.id_avail, a.start_time, a.end_time
                FROM availabilities AS a
                WHERE a.id_coach = ?
                  AND a.day_of_week = ?
                  AND a.is_available = 1
                ORDER BY a.start_time
            ");
            $stmtAvail->execute([$coach_id, $day_of_week]);
            $slots = $stmtAvail->fetchAll();

            if (empty($slots)) {
                $error = 'Ce coach n’a pas de disponibilité pour le ' . date('l d/m/Y', strtotime($date)) . '.';
            } else {
                // Tout est bon → passer à l’étape “choose_slot”
                $step = 'choose_slot';
            }
        }
    }
}

// -----------------------------------------------
//  Étape 2 : Choisir un créneau horaire précis
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'choose_slot') {
    // On récupère à nouveau ces valeurs depuis le POST pour pouvoir les réutiliser
    $coach_id    = (int) ($_POST['coach_id'] ?? 0);
    $date        = trim($_POST['date'] ?? '');
    $slot_id     = (int) ($_POST['slot_id'] ?? 0);

    // Validation basique : coach_id, date, slot_id
    if ($coach_id <= 0 || empty($date) || $slot_id <= 0) {
        $error = 'Données invalides. Veuillez recommencer la sélection.';
    } else {
        // 4.b) Récupérer les détails du créneau (start_time, end_time) depuis la table availabilities
        $stmtSlot = $pdo->prepare("
            SELECT start_time, end_time
            FROM availabilities
            WHERE id_avail = ?
              AND id_coach = ?
              AND is_available = 1
            LIMIT 1
        ");
        $stmtSlot->execute([$slot_id, $coach_id]);
        $slot = $stmtSlot->fetch();

        if (!$slot) {
            $error = 'Créneau invalide ou plus disponible.';
        } else {
            $start_time = $slot['start_time'];
            $end_time   = $slot['end_time'];

            // Passer à l’étape “confirm” pour afficher la page de confirmation (si vous voulez)
            $step = 'confirm';
        }
    }
}

// -----------------------------------------------
//  Étape 3 : Confirmer la réservation → INSERT
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'confirm') {
    // Nous récupérons tout ce dont on a besoin dans le POST : coach_id, date, slot_id
    $coach_id        = (int) ($_POST['coach_id'] ?? 0);
    $date            = trim($_POST['date'] ?? '');
    $slot_id         = (int) ($_POST['slot_id'] ?? 0);
    $start_time      = trim($_POST['start_time'] ?? '');
    $end_time        = trim($_POST['end_time'] ?? '');

    // Vérification basique : tous les champs doivent être présents
    if ($coach_id <= 0 || empty($date) || $slot_id <= 0 || empty($start_time) || empty($end_time)) {
        $error = 'Données manquantes ou invalides pour la réservation.';
        // Remettre à l’étape 2 pour que l’utilisateur recommence
        $step = 'choose_slot';
    } else {
        // Vérifier qu’il n’y a pas déjà un rendez-vous confirmé pour ce coach à cette date/heure
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) AS count
            FROM appointments
            WHERE id_coach = :id_coach
              AND appointment_date = :appointment_date
              AND start_time = :start_time
        ");
        $stmtCheck->execute([
            ':id_coach'        => $coach_id,
            ':appointment_date'=> $date,
            ':start_time'      => $start_time
        ]);
        $rowCheck = $stmtCheck->fetch();
        if ($rowCheck && $rowCheck['count'] > 0) {
            $error = 'Ce créneau est déjà réservé. Veuillez choisir un autre créneau ou une autre date.';
            // On revient à l’étape 2 pour afficher de nouveau les créneaux
            $step = 'choose_coach_date';
        } else {
            // 4.c) Insertion dans la table appointments
            $stmtIns = $pdo->prepare("
                INSERT INTO appointments 
                    (id_coach, id_user, appointment_date, start_time, end_time)
                VALUES 
                    (:id_coach, :id_user, :appointment_date, :start_time, :end_time)
            ");
            try {
                $stmtIns->execute([
                    ':id_coach'        => $coach_id,
                    ':id_user'         => $_SESSION['user_id'], 
                    ':appointment_date'=> $date,
                    ':start_time'      => $start_time,
                    ':end_time'        => $end_time
                ]);
                $success = 'Votre rendez-vous a bien été pris en compte pour le ' 
                         . date('d/m/Y', strtotime($date)) 
                         . ' de ' . substr($start_time, 0, 5) 
                         . ' à ' . substr($end_time, 0, 5) 
                         . '.';
                // Après succès, revenir à l’étape 1 pour une éventuelle nouvelle réservation
                $step = 'choose_coach_date';
            } catch (PDOException $e) {
                $error = 'Erreur lors de la réservation : ' . $e->getMessage();
                // On reste à l’étape confirm pour que l’utilisateur puisse réessayer
            }
        }
    }
}

// 5) Si on est dans les étapes “choose_slot” ou “confirm”, il faut également
//    récupérer les informations du coach et (pour choose_slot) la liste des créneaux.
//    Pour “choose_slot”, on suppose que $coach_id et $date sont valides.
// -----------------------------------------------

// Si on est à l’étape “choose_slot” (ou si on vient de POST-choose_coach_date sans erreur),
// on a besoin de $slots, $coach_id, $date, $day_of_week, etc.
// Ces variables ont été définies dans le bloc “if ($_SERVER... && $step==='choose_coach_date')”
// ou “if ($_SERVER... && $step==='choose_slot')”.

// Si on est à l’étape “confirm”, on a besoin de $coach_id, $date, $slot_id, $start_time, $end_time
// (définis plus haut dans le bloc “if ($_SERVER... && $step==='choose_slot')”).

// Pour simplifier l’affichage, on peut récupérer les infos du coach à chaque étape :
if (isset($coach_id) && $coach_id > 0) {
    $stmtC = $pdo->prepare("
        SELECT u.first_name, u.last_name, c.specialty
        FROM coaches AS c
        JOIN users AS u ON c.id_user = u.id_user
        WHERE c.id_coach = ?
        LIMIT 1
    ");
    $stmtC->execute([$coach_id]);
    $coachInfos = $stmtC->fetch();
    if (!$coachInfos) {
        // Si l’ID du coach est invalide, forcer un retour à l’étape 1
        $step = 'choose_coach_date';
        unset($coach_id, $date, $day_of_week, $slots, $slot_id, $start_time, $end_time);
    }
    // On peut réutiliser $coachInfos['first_name'], ['last_name'], ['specialty'] plus bas
}

// Fonction utilitaire pour afficher le nom du jour
function jourEnTexte($num) {
    $jours = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'
    ];
    return $jours[$num] ?? 'Inconnu';
}

require_once 'header.php';
?>

<!-- ===================== CONTENU SPÉCIFIQUE À reserver.php ===================== -->
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <h2 class="text-3xl font-bold mb-6 text-center">Réserver un coach</h2>

    <!-- Affichage des messages d’erreur ou de succès -->
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 border border-red-400 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 border border-green-400 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- ======== ÉTAPE 1 : Choisir coach + date ======== -->
    <?php if ($step === 'choose_coach_date'): ?>
        <form action="reserver.php" method="POST" class="space-y-6 bg-white rounded-xl shadow-md p-6">
            <input type="hidden" name="step" value="choose_coach_date">

            <div>
                <label for="coach_id" class="block text-gray-700 font-medium mb-2">Choisissez un coach</label>
                <select id="coach_id" name="coach_id" required
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Sélectionnez un coach --</option>
                    <?php foreach ($coachsList as $c): ?>
                        <option value="<?= $c['id_coach'] ?>"
                            <?= (isset($coach_id) && $coach_id == $c['id_coach']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialty'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="date" class="block text-gray-700 font-medium mb-2">Sélectionnez une date</label>
                <input type="date" id="date" name="date" required
                       class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="<?= htmlspecialchars($date ?? '') ?>"
                       min="<?= date('Y-m-d') ?>"
                       title="Entrez une date valide (aujourd’hui ou plus tard)">
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                Rechercher les créneaux
            </button>
        </form>
    <?php endif; ?>

    <!-- ======== ÉTAPE 2 : Choisir un créneau ======== -->
    <?php if ($step === 'choose_slot' && !empty($slots) && isset($coachInfos) && isset($date)): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">
                Créneaux disponibles pour <?= htmlspecialchars($coachInfos['first_name'] . ' ' . $coachInfos['last_name']) ?>
                <br>
                le <?= date('d/m/Y', strtotime($date)) ?> (<?= jourEnTexte((int)date('N', strtotime($date))) ?>)
            </h3>
            <form action="reserver.php" method="POST" class="space-y-6">
                <input type="hidden" name="step" value="choose_slot">
                <input type="hidden" name="coach_id" value="<?= htmlspecialchars($coach_id) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">

                <div>
                    <p class="mb-2 text-gray-700 font-medium">Sélectionnez un créneau horaire :</p>
                    <?php foreach ($slots as $slot):
                        $start = substr($slot['start_time'], 0, 5);
                        $end   = substr($slot['end_time'], 0, 5);
                    ?>
                        <div class="flex items-center mb-2">
                            <input
                                type="radio"
                                id="slot_<?= $slot['id_avail'] ?>"
                                name="slot_id"
                                value="<?= $slot['id_avail'] ?>"
                                required
                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <label for="slot_<?= $slot['id_avail'] ?>" class="ml-2 text-gray-700">
                                <?= htmlspecialchars($start . ' – ' . $end) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit"
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-medium">
                    Confirmer le créneau
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ======== ÉTAPE 3 : Confirmation & Récapitulatif (facultatif) ======== -->
    <?php if ($step === 'confirm' && isset($coachInfos) && isset($slot_id)): ?>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4">Récapitulatif de votre choix</h3>
            <p class="mb-2"><strong>Coach :</strong> <?= htmlspecialchars($coachInfos['first_name'] . ' ' . $coachInfos['last_name']) ?></p>
            <p class="mb-2"><strong>Spécialité :</strong> <?= htmlspecialchars($coachInfos['specialty']) ?></p>
            <p class="mb-2"><strong>Date :</strong> <?= date('d/m/Y', strtotime($date)) ?> (<?= jourEnTexte((int)date('N', strtotime($date))) ?>)</p>
            <p class="mb-2"><strong>Heure :</strong> <?= substr($start_time, 0, 5) ?> – <?= substr($end_time, 0, 5) ?></p>

            <form action="reserver.php" method="POST" class="mt-6 space-y-6">
                <input type="hidden" name="step" value="confirm">
                <input type="hidden" name="coach_id" value="<?= htmlspecialchars($coach_id) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                <input type="hidden" name="slot_id" value="<?= htmlspecialchars($slot_id) ?>">
                <input type="hidden" name="start_time" value="<?= htmlspecialchars($start_time) ?>">
                <input type="hidden" name="end_time" value="<?= htmlspecialchars($end_time) ?>">

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                    Confirmer ma réservation
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php
// ===================== FIN reserver.php =====================
require_once 'footer.php';
?>
