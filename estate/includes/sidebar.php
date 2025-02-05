<div class="w-64 bg-slate-800 text-white flex flex-col">
    <!-- Header -->
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <i class="fas fa-id-card text-2xl text-blue-400"></i>
            <h2 class="text-xl font-bold">Gate Pass System</h2>
        </div>
        <div class="mt-2 text-sm text-gray-400">
            Estate Officer Dashboard
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <li>
                <a href="index.php" class="flex items-center space-x-3 px-4 py-3 bg-slate-700 rounded-lg">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="approvals.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-check-circle w-5"></i>
                    <span>Final Approvals</span>
                </a>
            </li>
            <li>
                <a href="departments.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-sitemap w-5"></i>
                    <span>Department Overview</span>
                </a>
            </li>
            <li>
                <a href="analytics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-file-pdf w-5"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="history.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-history w-5"></i>
                    <span>History</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
            </li>
            <!-- kipengele cha emergency gatepass kwa estate officer-->
            <!-- <li>
                <a href="emergency_gatepass.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-cog w-5"></i>
                    <span>Emergency GatePass</span>
                </a>
            </li> -->
        </ul>
    </nav>
    
    <!-- Logout -->
    <div class="p-4 border-t border-slate-700">
        <a href="../logout.php" class="flex items-center space-x-3 px-4 py-3 text-red-400 rounded-lg hover:bg-slate-700 transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</div>