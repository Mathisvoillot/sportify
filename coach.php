<?php
// coach.php
require_once 'config.php';

// 1) Validation du paramètre id (doit exister et être numérique)
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit('ID de coach invalide.');
}
$id_coach = (int) $_GET['id'];

// 2) Récupérer les informations du coach + son utilisateur
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, c.specialty, c.photo_path, c.cv_path, c.bio
    FROM coaches AS c
    INNER JOIN users AS u ON c.id_user = u.id_user
    WHERE c.id_coach = ?
    LIMIT 1
");
$stmt->execute([$id_coach]);
$coach = $stmt->fetch();

if (!$coach) {
    exit('Coach non trouvé.');
}

// 3) Récupérer les disponibilités du coach (par jour de la semaine)
$stmt2 = $pdo->prepare("
    SELECT day_of_week, start_time, end_time, is_available
    FROM availabilities
    WHERE id_coach = ?
    ORDER BY day_of_week, start_time
");
$stmt2->execute([$id_coach]);
$dispos = $stmt2->fetchAll();

// 4) Fonction utilitaire pour convertir un numéro de jour en texte
function jourEnTexte($num) {
    $jours = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'
    ];
    return $jours[$num] ?? 'Inconnu';
}

require_once 'header.php';
?>

<!-- ===================== CONTENU SPÉCIFIQUE À coach.php ===================== -->
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Bloc gauche : photo + infos générales -->
        <div class="col-span-1">
            <?php
            $photo = $coach['photo_path']
                ? 'uploads/photos/' . htmlspecialchars($coach['photo_path'])
                : 'https://via.placeholder.com/400x400?text=Coach';
            ?>
            <img src="<?= $photo ?>"
                 alt="Photo de <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>"
                 class="w-full rounded-xl shadow-lg mb-6">

            <h2 class="text-2xl font-semibold mb-2"><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></h2>
            <p class="text-gray-600 mb-4"><?= htmlspecialchars($coach['specialty']) ?></p>

            <?php if ($coach['cv_path']): ?>
                <a href="uploads/cvs/<?= htmlspecialchars($coach['cv_path']) ?>" target="_blank" class="text-blue-600 hover:underline mb-4 inline-block">
                    <i class="fas fa-file-pdf mr-2"></i>Voir le CV
                </a>
            <?php endif; ?>

            <div class="mt-6">
                <h3 class="text-xl font-bold mb-2">Biographie</h3>
                <p class="text-gray-700"><?= nl2br(htmlspecialchars($coach['bio'] ?? 'Aucune biographie disponible.')) ?></p>
            </div>
        </div>

        <!-- Bloc droit : tableau des disponibilités + lien Réserver -->
        <div class="col-span-2">
            <h3 class="text-2xl font-semibold mb-4">Disponibilités hebdomadaires</h3>
            <?php if (empty($dispos)): ?>
                <p class="text-gray-500">Ce coach n'a pas encore défini de disponibilités.</p>
            <?php else: ?>
                <table class="min-w-full bg-white rounded-lg shadow overflow-hidden">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">Jour</th>
                            <th class="px-4 py-2 text-left">De</th>
                            <th class="px-4 py-2 text-left">À</th>
                            <th class="px-4 py-2 text-left">Statut</th>
                            <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                                <th class="px-4 py-2 text-left">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dispos as $d): ?>
                            <tr class="<?= $d['is_available'] ? '' : 'bg-gray-100' ?>">
                                <td class="border px-4 py-2"><?= jourEnTexte($d['day_of_week']) ?></td>
                                <td class="border px-4 py-2"><?= substr($d['start_time'], 0, 5) ?></td>
                                <td class="border px-4 py-2"><?= substr($d['end_time'], 0, 5) ?></td>
                                <td class="border px-4 py-2">
                                    <?php if ($d['is_available']): ?>
                                        <span class="text-green-600 font-medium">Disponible</span>
                                    <?php else: ?>
                                        <span class="text-red-600 font-medium">Indisponible</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                                    <td class="border px-4 py-2">
                                        <?php if ($d['is_available']): ?>
                                            <!-- Lien vers réserver.php avec jour et horaires en GET -->
                                            <a href="réserver.php?coach_id=<?= $id_coach ?>&day=<?= $d['day_of_week'] ?>&start=<?= substr($d['start_time'], 0, 5) ?>&end=<?= substr($d['end_time'], 0, 5) ?>"
                                               class="text-blue-600 hover:underline">Réserver</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">–</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// ===================== FIN coach.php =====================
require_once 'footer.php';
