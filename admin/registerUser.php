<?php
include('../backend/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = htmlspecialchars(trim($_POST['password']));
    $role = $_POST['role'];
    
    if (!empty($name) && !empty($email) && !empty($phone) && !empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO Users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword, $role]);

        $_SESSION['success'] = "Registration successful. Please log in!";
        header('Location: login.php');
    } else {
        $_SESSION['error'] = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="max-w-md mx-auto mt-20 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-center mb-6">Register</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='bg-red-500 text-white p-2 rounded mb-4'>".$_SESSION['error']."</div>";
            unset($_SESSION['error']);
        }
        ?>

        <form method="POST">
            <div class="mb-4">
                <label for="name" class="block text-gray-700">Name</label>
                <input type="text" name="name" class="w-full p-2 border border-gray-300 rounded mt-1" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" name="email" class="w-full p-2 border border-gray-300 rounded mt-1" required>
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-gray-700">Phone</label>
                <input type="text" name="phone" class="w-full p-2 border border-gray-300 rounded mt-1" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" name="password" class="w-full p-2 border border-gray-300 rounded mt-1" required>
            </div>

            <div class="mb-4">
                <label for="role" class="block text-gray-700">Role</label>
                <select name="role" class="w-full p-2 border border-gray-300 rounded mt-1" required>
                    <option value="Applicant">Applicant</option>
                    <option value="HoD">HoD</option>
                    <option value="Estate Officer">Estate Officer</option>
                    <option value="Gate Inspector">Gate Inspector</option>
                </select>
            </div>

            <button type="submit" class="w-full p-3 bg-blue-500 text-white rounded hover:bg-blue-600">Register</button>
        </form>
    </div>

</body>
</html>
