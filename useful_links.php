<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white py-4">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <h1 class="text-2xl font-semibold">TrafAnalyz</h1>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="login.php" class="hover:text-blue-200 transition duration-300">Login</a></li>
                    <li><a href="register.php" class="hover:text-blue-200 transition duration-300">Register</a></li>
                    <li><a href="dashboard.php" class="hover:text-blue-200 transition duration-300">Dashboard</a></li>
                    <li><a href="logout.php" class="hover:text-blue-200 transition duration-300">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <section class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Analyze Your Web Traffic with Ease</h2>
            <p class="text-gray-600 text-lg mb-6">TrafAnalyz provides powerful web analytics tools to help you understand your visitors, their behavior, and optimize your website performance.</p>
            <div class="flex justify-center space-x-4">
                <a href="login.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full transition duration-300">Login</a>
                <a href="register.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-full transition duration-300">Create Account</a>
            </div>
        </section>

        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 text-center mb-8">Why Choose TrafAnalyz?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center">
                    <img src="https://placehold.co/100x100/EEE/31343C" alt="Interactive Charts" class="mb-4 rounded-full">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">Interactive Charts</h3>
                    <p class="text-gray-600 text-center">Visualize your traffic data with beautiful interactive charts that help you identify trends and patterns.</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center">
                    <img src="https://placehold.co/100x100/EEE/31343C" alt="Comparative Analysis" class="mb-4 rounded-full">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">Comparative Analysis</h3>
                    <p class="text-gray-600 text-center">Compare traffic data from different time periods to better understand your website's growth and performance.</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center">
                    <img src="https://placehold.co/100x100/EEE/31343C" alt="Source Tracking" class="mb-4 rounded-full">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">Source Tracking</h3>
                    <p class="text-gray-600 text-center">Discover where your visitors are coming from and which marketing channels are most effective.</p>
                </div>
            </div>
        </section>

        <section class="bg-blue-100 rounded-lg py-6 px-4 text-center">
            <h2 class="text-2xl font-semibold text-blue-700 mb-4">Ready to Get Started?</h2>
            <p class="text-gray-600 text-lg mb-6">Join TrafAnalyz today and unlock the power of web analytics to make informed decisions and grow your online presence.</p>
            <a href="register.php" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-full transition duration-300 text-lg">Get Started Now</a>
        </section>

        <section class="mt-12">
            <h2 class="text-2xl font-semibold text-gray-800 text-center mb-8">Useful Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-start">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">Google Analytics</h3>
                    <p class="text-gray-600 mb-4">Learn about Google Analytics.</p>
                    <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:text-blue-700 transition duration-300">Visit Website</a>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-start">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">SimilarWeb</h3>
                    <p class="text-gray-600 mb-4">Explore SimilarWeb for competitive analysis.</p>
                    <a href="https://www.similarweb.com/" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:text-blue-700 transition duration-300">Visit Website</a>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-start">
                    <h3 class="text-xl font-semibold text-blue-600 mb-2">W3Counter</h3>
                    <p class="text-gray-600 mb-4">Check out W3Counter for website statistics.</p>
                    <a href="https://www.w3counter.com/" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:text-blue-700 transition duration-300">Visit Website</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-200 text-gray-700 py-4 text-center mt-8">
        <p>&copy; <?php echo date("Y"); ?> TrafAnalyz. All rights reserved.</p>
    </footer>
</body>
</html>
