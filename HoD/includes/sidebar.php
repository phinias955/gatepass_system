<div class="w-64 bg-slate-800 text-white flex flex-col h-screen fixed">
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <i class="fas fa-user-tie text-2xl text-blue-400"></i>
            <h2 class="text-xl font-bold">Gate Pass System</h2>
        </div>
        <div class="mt-2 text-sm text-gray-400">
            HoD Dashboard
        </div>
    </div>
    
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center space-x-3 px-4 py-3 bg-slate-700 rounded-lg">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="pending.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-clock w-5"></i>
                    <span>Pending Approvals</span>
                </a>
            </li>
            <li>
                <a href="history.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-history w-5"></i>
                    <span>Approval History</span>
                </a>
            </li>
            <li>
                <a href="stats.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Department Stats</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="p-4 border-t border-slate-700">
        <a href="../logout.php" class="flex items-center space-x-3 px-4 py-3 text-red-400 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</div>