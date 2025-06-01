<?php
require_once 'config.php';

// si l’utilisateur est déjà connecté reddirection vers l’accueil
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // recup l’utilisateur par email
        $stmt = $pdo->prepare("SELECT id_user, password_hash, first_name, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // verif le mdp
        if ($user && password_verify($password, $user['password_hash'])) {
            // authentification reussie on stock les infor en session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

require_once 'header.php';
?>

<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold mb-6 text-center">Connexion</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 border border-red-400 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="bg-white rounded-xl shadow-md p-6">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" required
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-medium mb-2">Mot de passe</label>
            <input type="password" id="password" name="password" required
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">Se connecter</button>
    </form>
</div>

<?php
require_once 'footer.php';
