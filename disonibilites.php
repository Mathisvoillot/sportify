<?php
// liste les creneaux disponibles et permet de reserver 

require_once 'config.php';

// seuls les clients peuvent acceder a cette page
if (!isLoggedIn() || getUserRole() !== 'client') {
    header('Location: login.php');
    exit();
}

// fonctions utilitaires

function jourEnTexte(int $num): string {
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


function prochaineDatePourJour(int $day_of_week): string {
    $today = new DateTime('today');
    $todayDow = (int) $today->format('N'); 
    $delta = ($day_of_week - $todayDow + 7) % 7;
    $future = (clone $today)->add(new DateInterval('P' . $delta . 'D'));
    return $future->format('Y-m-d');
}

// réservation d’un créneau
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['action']) 
    && $_POST['action'] === 'book_slot'
) {
    // recuperer les champs du formulaire
    $coach_id         = (int) ($_POST['coach_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $start_time       = trim($_POST['start_time'] ?? '');
    $end_time         = trim($_POST['end_time'] ?? '');

    // validation basique
    if ($coach_id <= 0
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)
        || !preg_match('/^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/', $start_time)
        || !preg_match('/^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/', $end_time)
    ) {
        $error = 'Données invalides pour la réservation.';
    } else {
        // verifier que la date n est pas passe
        $today = (new DateTime('today'))->format('Y-m-d');
        if ($appointment_date < $today) {
            $error = 'Vous ne pouvez pas réserver une date antérieure à aujourd’hui.';
        } else {
            // verifier  coach existe 
            $stmtChkCoach = $pdo->prepare("SELECT COUNT(*) AS count FROM coaches WHERE id_coach = ?");
            $stmtChkCoach->execute([$coach_id]);
            $rowChkCoach = $stmtChkCoach->fetch();
            if (!$rowChkCoach || $rowChkCoach['count'] == 0) {
                $error = 'Le coach sélectionné est introuvable.';
            } else {
                // verifier qu il n y a pas de creneau deja sur cette selection
                $stmtCheck = $pdo->prepare("
                    SELECT COUNT(*) AS count
                    FROM appointments
                    WHERE id_coach = :id_coach
                      AND appointment_date = :appointment_date
                      AND start_time = :start_time
                ");
                $stmtCheck->execute([
                    ':id_coach'        => $coach_id,
                    ':appointment_date'=> $appointment_date,
                    ':start_time'      => $start_time
                ]);
                $rowCheck = $stmtCheck->fetch();
                if ($rowCheck && $rowCheck['count'] > 0) {
                    $error = 'Ce créneau est déjà réservé. Choisissez un autre créneau ou une autre date.';
                } else {
                    // ajouter la nouvelle reservation dans appointments
                    $stmtIns = $pdo->prepare("
                        INSERT INTO appointments
                            (id_coach, id_client, appointment_date, start_time, end_time)
                        VALUES
                            (:id_coach, :id_client, :appointment_date, :start_time, :end_time)
                    ");
                    try {
                        $stmtIns->execute([
                            ':id_coach'        => $coach_id,
                            ':id_client'       => $_SESSION['user_id'],
                            ':appointment_date'=> $appointment_date,
                            ':start_time'      => $start_time,
                            ':end_time'        => $end_time
                        ]);
                        $success = 'Votre rendez-vous a été pris en compte pour le '
                                 . date('d/m/Y', strtotime($appointment_date))
                                 . ' de ' . substr($start_time, 0, 5)
                                 . ' à ' . substr($end_time, 0, 5) . '.';
                    } catch (PDOException $e) {
                        $error = 'Erreur lors de la réservation : ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// afficher que les disponibilités demandées
$whereClause = 'a.is_available = 1';
$params      = [];

// si on passe coach_id en GET, on filtre
if (isset($_GET['coach_id']) && ctype_digit($_GET['coach_id'])) {
    $coach_id_filter     = (int) $_GET['coach_id'];
    $whereClause       .= ' AND a.id_coach = :coach_id';
    $params[':coach_id'] = $coach_id_filter;
}

// requete qui recup toutes les disponibilites ou uniquement celles du coach
$sql = "
    SELECT 
        a.id_avail,
        a.id_coach,
        a.day_of_week,
        a.start_time,
        a.end_time,
        u.first_name,
        u.last_name,
        c.specialty
    FROM availabilities AS a
    JOIN coaches AS c ON a.id_coach = c.id_coach
    JOIN users   AS u ON c.id_user   = u.id_user
    WHERE $whereClause
    ORDER BY u.last_name, u.first_name, a.day_of_week, a.start_time
";
$stmtAll = $pdo->prepare($sql);
$stmtAll->execute($params);
$allSlots = $stmtAll->fetchAll();

require_once 'header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold mb-6 text-center">Disponibilités des coachs</h2>

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

    <!-- tableau des dispo -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-md overflow-hidden">
            <thead class="bg-blue-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Coach</th>
                    <th class="px-4 py-2 text-left">Spécialité</th>
                    <th class="px-4 py-2 text-left">Jour</th>
                    <th class="px-4 py-2 text-left">De</th>
                    <th class="px-4 py-2 text-left">À</th>
                    <th class="px-4 py-2 text-left">Date de RDV</th>
                    <th class="px-4 py-2 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allSlots as $slot):
                    // calculer la prochaine date pour le jour de la semaine
                    $nextDate = prochaineDatePourJour((int)$slot['day_of_week']);
                    // Formater les heures (HH:MM)
                    $startHM = substr($slot['start_time'], 0, 5);
                    $endHM   = substr($slot['end_time'], 0, 5);
                ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <?= htmlspecialchars($slot['first_name'] . ' ' . $slot['last_name']) ?>
                        </td>
                        <td class="px-4 py-2"><?= htmlspecialchars($slot['specialty']) ?></td>
                        <td class="px-4 py-2"><?= jourEnTexte((int)$slot['day_of_week']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($startHM) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($endHM) ?></td>
                        <td class="px-4 py-2">
                            <input 
                                type="date" 
                                form="frm_<?= $slot['id_avail'] ?>"
                                name="appointment_date" 
                                class="border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= $nextDate ?>"
                                min="<?= date('Y-m-d') ?>"
                                required
                            >
                        </td>
                        <td class="px-4 py-2">
                            <button 
                                form="frm_<?= $slot['id_avail'] ?>"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium"
                            >
                                Réserver
                            </button>
                        </td>
                    </tr>

                    <!-- formulaire cache pour chaque creneau -->
                    <form 
                        id="frm_<?= $slot['id_avail'] ?>" 
                        action="disponibilites.php" 
                        method="POST" 
                        class="hidden"
                    >
                        <input type="hidden" name="action" value="book_slot">
         <input type="hidden" name="coach_id" value="<?= htmlspecialchars($slot['id_coach']) ?>">
                         
                         <input type="hidden" name="start_time" value="<?= htmlspecialchars($slot['start_time']) ?>">
                        <input type="hidden" name="end_time" value="<?= htmlspecialchars($slot['end_time']) ?>">
                        <!-- Le <input type="date"> est déjà lié grâce à form="frm_..." -->
                    </form>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
?>
