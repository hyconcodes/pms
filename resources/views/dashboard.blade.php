<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <!-- User Registration Chart -->
            <div
                class="relative h-[300px] overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">User Registrations (Last 30 Days)
                </h3>
                <div class="h-[calc(100%-2rem)]">
                    <canvas id="registrationChart"></canvas>
                </div>
            </div>

            <!-- Role Distribution Chart -->
            <div
                class="relative h-[300px] overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">User Role Distribution</h3>
                <div class="h-[calc(100%-2rem)]">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Registration Chart
                const regCtx = document.getElementById('registrationChart').getContext('2d');
                const labels = @json($labels ?? array_fill(0, 30, ''));
                const registrationSeries = @json($registrationSeries ?? array_fill(0, 30, 0));

                new Chart(regCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Registrations',
                            data: registrationSeries,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22,163,74,0.15)',
                            tension: 0.35,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }
                    }
                });

                // Role Distribution Chart
                const roleCtx = document.getElementById('roleChart').getContext('2d');
                const doctorCount =
                    {{ DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', 'doctor')->count() }};
                const pharmacistCount =
                    {{ DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where(
                            'roles.name',
                            'pharmacist',
                        )->count() }};
                const patientCount =
                    {{ DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', 'patient')->count() }};
                const cashierCount =
                    {{ DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', 'cashier')->count() }};

                new Chart(roleCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Doctors', 'Pharmacists', 'Patients', 'Cashiers'],
                        datasets: [{
                            data: [doctorCount, pharmacistCount, patientCount, cashierCount],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(255, 206, 86, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            });
        </script>

        <!-- Patient Management Table -->
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-4">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Recent Patients</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead>
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Name</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Email</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Registration Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach (App\Models\User::role('patient')->latest()->take(5)->get() as $patient)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ $patient->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ $patient->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ $patient->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
