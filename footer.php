<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheelsdoc</title>
    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Brand Section -->
                <div class="lg:col-span-1">
                    <h3 class="text-xl font-bold mb-4 text-blue-400">Wheelsdoc</h3>
                    <p class="text-gray-400 mb-4">Your comprehensive solution for all vehicle rental, service, and parts needs. We provide reliable automotive services with professionalism and care.</p>
                    <div class="flex space-x-3">
                        <div class="bg-blue-600 p-2 rounded-full">
                            <i class="fas fa-car text-white"></i>
                        </div>
                        <div class="bg-green-600 p-2 rounded-full">
                            <i class="fas fa-tools text-white"></i>
                        </div>
                        <div class="bg-red-600 p-2 rounded-full">
                            <i class="fas fa-cog text-white"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition duration-300 flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i> Car Rental</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition duration-300 flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i> Vehicle Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition duration-300 flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i> Buy Parts</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition duration-300 flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i> Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition duration-300 flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i> About Us</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Contact Info</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-400 mt-1 mr-3"></i>
                            <span class="text-gray-400">123 Automotive Avenue<br>Dhaka, Bangladesh</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone text-blue-400 mr-3"></i>
                            <span class="text-gray-400">+880 1XXX-XXXXXX</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-blue-400 mr-3"></i>
                            <span class="text-gray-400">info@vms.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock text-blue-400 mr-3"></i>
                            <span class="text-gray-400">Mon-Sat: 8:00 AM - 8:00 PM</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Social Media & Newsletter -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Follow Us</h3>
                    <div class="flex space-x-4 mb-6">
                        <a href="#" class="bg-gray-800 hover:bg-blue-600 text-white p-3 rounded-full transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-gray-800 hover:bg-blue-400 text-white p-3 rounded-full transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="bg-gray-800 hover:bg-pink-600 text-white p-3 rounded-full transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Newsletter</h3>
                    <p class="text-gray-400 mb-3">Subscribe to get updates on new vehicles and offers.</p>
                    <div class="flex">
                        <input type="email" placeholder="Your email" class="bg-gray-800 text-white px-4 py-2 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-lg transition duration-300">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Copyright Section -->
            <div class="border-t border-gray-800 mt-10 pt-6 text-center">
                <p class="text-gray-400">&copy; 2023 Vehicle Management System. All rights reserved.</p>
                <div class="flex justify-center space-x-6 mt-4 text-sm text-gray-500">
                    <a href="#" class="hover:text-blue-400 transition duration-300">Privacy Policy</a>
                    <a href="#" class="hover:text-blue-400 transition duration-300">Terms of Service</a>
                    <a href="#" class="hover:text-blue-400 transition duration-300">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>