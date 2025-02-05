<div class="w-64 bg-slate-800 text-white flex flex-col h-screen fixed">
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <i class="fas fa-university text-2xl text-blue-400"></i> 
            <h2 class="text-xl font-bold">Gate Pass System</h2>
        </div>
        <div class="mt-2 text-sm text-gray-400">
            Applicant Dashboard
        </div>
    </div>
    
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
                    <i class="fas fa-tachometer-alt text-gray-400"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="newapp.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
                    <i class="fas fa-file-alt text-gray-400"></i> 
                    <span>New Application</span>
                </a>
            </li>
            <li>
                <a href="moventreq.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
                    <i class="fas fa-exchange-alt text-gray-400"></i> 
                    <span>Movement Request</span>
                </a>
            </li>
            <li>
                <a href="history.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
                    <i class="fas fa-clipboard-list text-gray-400"></i> 
                    <span>Application History</span>
                </a>
            </li>
            <li>
                <a href="movement_history.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
                    <i class="fas fa-clipboard-list text-gray-400"></i> 
                    <span>Movement History</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-sliders-h w-5"></i> 
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-4 border-t border-slate-700">
        <a href="../logout.php" class="flex items-center space-x-2 p-2 hover:bg-slate-700 rounded">
            <i class="fas fa-sign-out-alt text-gray-400"></i>
            <span>Logout</span>
        </a>
    </div>
</div>