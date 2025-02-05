<header class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <div class="flex items-center space-x-4">
                    <span class="text-lg font-semibold text-gray-700">Applicant Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    
                    <span class=" txt-formart text-gray-700 upcase"><?php  echo htmlspecialchars($_SESSION['name']); ?></span>
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                    </button>
                </div>
            </div>
        </header>