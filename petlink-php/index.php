<?php
    require_once 'core/init.php';
    session_start();
    // This checks if the 'user' key exists in the session array.
    $is_logged_in = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Visit Record System - PetLink Caloocan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/fonts.css"> <!-- Local Poppins Font -->
    <link rel="stylesheet" href="assets/boxicons/css/boxicons.min.css">
</head>
<body>
    <div class="sidebar-overlay"></div>
    
    <!-- Login Container: Only shows if the user is NOT logged in -->
    <div id="login-container" class="<?php echo $is_logged_in ? 'hidden' : ''; ?>">
        <div class="login-box">
            <h1>PetLink Caloocan</h1>
            <p>Pet Visit Record System</p>
            <form id="login-form">
                <div class="form-group">
                    <input type="text" id="username" placeholder="Username (e.g., admin)" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" placeholder="Password (e.g., admin123)" required>
                </div>
                <p id="login-error" class="hidden"></p>
                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </div>

    <!-- Main App Container: Only shows if the user IS logged in -->
    <div id="app-container" class="container <?php echo !$is_logged_in ? 'hidden' : ''; ?>">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">PVRS</div>
                <button class="close-sidebar-btn" id="close-sidebar-btn">&times;</button>
            </div>
            <div class="user-profile">
                <h2 id="sidebar-user-name">User Name</h2>
                <p id="sidebar-user-role">Role</p>
                <button id="change-password-btn" class="secondary-btn-sidebar">Change Password</button>
            </div>
            <nav class="navigation">
                <ul>
                    <li class="active"><a href="#" data-target="dashboard" data-title="Good day!"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
                    <li><a href="#" data-target="appointments" data-title="Manage Appointments"><i class='bx bx-calendar'></i><span>Appointments</span></a></li>
                    <li><a href="#" data-target="records" data-title="Pet Records Directory"><i class='bx bx-folder'></i><span>Pet Records</span></a></li>
                    <li><a href="#" data-target="reports" data-title="Clinic Reports"><i class='bx bx-bar-chart-alt-2'></i><span>Reports</span></a></li>
                    <li><a href="#" data-target="clients" data-title="Client Directory"><i class='bx bxs-contact'></i><span>Clients</span></a></li>
                    <li id="manage-users-nav" class="hidden"><a href="#" data-target="users" data-title="Manage Users"><i class='bx bxs-user-account'></i><span>Manage Users</span></a></li>
                    <li id="action-log-nav" class="hidden"><a href="#" data-target="action-log" data-title="Action Log"><i class='bx bx-history'></i><span>Action Log</span></a></li>
                    <li id="recycle-bin-nav" class="hidden"><a href="#" data-target="recycle-bin" data-title="Recycle Bin"><i class='bx bxs-trash'></i><span>Recycle Bin</span></a></li>
                </ul>
            </nav>
                </ul>
            </nav>
            <button id="logout-btn" class="logout-btn"><i class='bx bx-log-out'></i><span>Logout</span></button>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <button class="menu-toggle-btn" id="menu-toggle-btn"><i class='bx bx-menu'></i></button>
                <h1 id="page-title">Good day!</h1>
            </header>

            <section data-page="dashboard" class="page-content active">
                <div class="content-body">
                    <div class="cards-grid">
                        <div class="card earnings-card"><div class="card-icon"><i class='bx bx-line-chart'></i></div><p>Today's Visits</p><h3 id="todays-visits-count">0</h3><span>+0 since yesterday</span></div>
                        <div class="card rank-card"><p>Urgent Follow-ups</p><h3 id="urgent-followups-count">0</h3><span>Requires immediate attention</span></div>
                        <div class="card projects-card"><p>New Patients</p><h3 id="new-patients-count">0</h3><span>this month</span><div class="tags"><span class="tag">Canine</span><span class="tag">Feline</span></div></div>
                    </div>
                    <div class="bottom-grid">
                        <section class="recent-visits"><h2>Recent Visits Log</h2><div id="recent-visits-list"><p class="placeholder-text">Loading recent visits...</p></div></section>
                        <section class="your-projects"><h2>Upcoming Appointments</h2><div id="upcoming-appointments-list"><p class="placeholder-text">Loading appointments...</p></div><a href="#" class="see-all">See all appointments</a></section>
                    </div>
                    <div class="bottom-row">
                        <div class="engage-card"><div class="engage-icon">#</div><div class="engage-text"><h3>Quick Actions</h3><p>Schedule a new appointment</p></div><button id="schedule-now-btn" class="join-now">Schedule Now</button></div>
                        <div class="recommended-project">
                            <div class="rec-header">
                                <div><p class="rec-owner" id="emergency-owner-name">No Emergencies</p><p class="rec-time" id="emergency-time">Queue is clear</p></div>
                                <span class="rec-tag">Walk-in</span>
                            </div>
                            <div class="rec-body"><h3 id="emergency-details">Waiting for next walk-in patient.</h3></div>
                        </div>
                    </div>
                </div>
            </section>

            <section data-page="appointments" class="page-content">
                <div class="page-header">
                    <div class="search-bar page-search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" id="appointments-search" placeholder="Search pets, owners, services...">
                    </div>
                    <button class="join-now" id="add-appointment-btn-page"><i class='bx bx-plus'></i> New Appointment</button>
                </div>
                <div class="table-container">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointments-table-body"></tbody>
                    </table>
                </div>
                <div class="pagination-controls" id="appointments-pagination"></div>
            </section>

            <section data-page="records" class="page-content">
                <div class="page-header"><div class="header-actions"><div class="search-bar page-search-bar"><i class='bx bx-search'></i><input type="text" id="pet-records-search" placeholder="Search pets or owners..."></div><button class="join-now" id="add-pet-btn"><i class='bx bx-plus'></i> Add Pet</button></div></div>
                <div class="pet-records-grid" id="pet-records-grid"></div>
            </section>

            <section data-page="pet-detail" class="page-content">
                <div class="pet-detail-top-bar">
                    <a href="#" class="back-to-records" id="back-to-records-link"><i class='bx bx-arrow-back'></i> Back to Pet Records</a>
                    <button class="join-now" id="new-visit-btn"><i class='bx bx-plus'></i> New Visit Record</button>
                </div>
                <div class="pet-detail-header">
                    <i id="pet-detail-icon" class='bx bxs-dog'></i>
                    <div>
                        <h1 id="pet-detail-name">Pet Name</h1>
                        <p id="pet-detail-owner">Owner: John Doe</p>
                    </div>
                </div>
                <div class="pet-detail-info-grid">
                    <div class="info-box"><strong>Species:</strong> <span id="pet-detail-species"></span></div>
                    <div class="info-box"><strong>Breed:</strong> <span id="pet-detail-breed"></span></div>
                    <div class="info-box"><strong>Sex:</strong> <span id="pet-detail-sex"></span></div>
                    <div class="info-box"><strong>DOB:</strong> <span id="pet-detail-dob"></span></div>
                </div>
                <h2>Visit History</h2>
                <div class="visit-history-container" id="pet-visit-history-container">
                </div>
            </section>
            
            <section data-page="reports" class="page-content">
                <div class="kpi-grid" id="reports-kpi-grid"><div class="kpi-card"><i class='bx bxs-group'></i><div class="kpi-text"><h3 id="total-patients-kpi">0</h3><p>Total Patients</p></div></div><div class="kpi-card"><i class='bx bxs-calendar-check'></i><div class="kpi-text"><h3 id="total-visits-kpi">0</h3><p>Total Visits Logged</p></div></div><div class="kpi-card"><i class='bx bxs-dog'></i><div class="kpi-text"><h3 id="canine-patients-kpi">0</h3><p>Canine Patients</p></div></div><div class="kpi-card"><i class='bx bxs-cat'></i><div class="kpi-text"><h3 id="feline-patients-kpi">0</h3><p>Feline Patients</p></div></div></div>
                <div class="chart-container"><h2>Visits Over Last 7 Days</h2><canvas id="visits-chart"></canvas></div>
            </section>

            <section data-page="clients" class="page-content">
                <div class="page-header">
                    <div class="search-bar page-search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" id="clients-search" placeholder="Search clients...">
                    </div>
                    <button class="join-now" id="add-client-btn-page"><i class='bx bx-plus'></i> New Client</button>
                </div>
                <div class="table-container">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clients-table-body"></tbody>
                    </table>
                </div>
            </section>

            <section data-page="users" class="page-content">
                <div class="page-header">
                    <button class="join-now" id="add-user-btn"><i class='bx bx-plus'></i> New User</button>   
                </div>
                <div class="backup-restore-container">
                    <h3>Backup & Restore</h3>
                    <p>Download a complete backup of the application data. The restore function will overwrite all existing data with the contents of the backup file.</p>
                    <div class="backup-restore-actions">
                        <button class="join-now secondary-btn" id="backup-db-btn"><i class='bx bxs-download'></i> Download Backup</button>
                        <form id="restore-form">
                            <label for="restore-file-input" class="join-now danger-btn">
                                <i class='bx bxs-upload'></i> Restore from File...
                            </label>
                            <input type="file" id="restore-file-input" accept=".sql" style="display: none;">
                        </form>
                    </div>
                </div>
                <div class="table-container">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body"></tbody>
                    </table>
                </div>
            </section>

            <section data-page="recycle-bin" class="page-content">
                <p style="margin-bottom: 15px; margin-top: 0;">The recycle bin data deletes automatically after 30 days.</p>
                <div id="recycle-bin-content">
                   
                </div>
            </section>

            <section data-page="action-log" class="page-content">
                <div class="page-header">
                    <div class="search-bar page-search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" id="action-log-search" placeholder="Search logs...">
                    </div>
                </div>
                <div class="table-container">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="action-log-table-body"></tbody>
                    </table>
                </div>
                <div class="pagination-controls" id="action-log-pagination"></div>
            </section>
        </main>
    </div>

    <!-- ===== MODALS (FIXED) ===== -->
    <!-- Add/Edit Appointment Modal -->
    <div class="modal-overlay" id="add-appointment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="appointment-modal-title">New Appointment</h2>
                <button class="close-modal-btn" data-modal-id="add-appointment-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="appointment-form">
                    <input type="hidden" id="appointmentId">
                    <div class="form-group"><label for="patient-select">Patient</label><select id="patient-select" required><option value="" disabled selected>Choose a pet...</option></select></div>
                    <div class="form-group"><label for="service">Service</label><input type="text" id="service" placeholder="e.g., Annual Checkup" required></div>
                    <div class="form-group"><label for="appointmentDate">Date & Time</label><input type="datetime-local" id="appointmentDate" required></div>
                    <div class="form-group"><label for="status-select">Status</label><select id="status-select"><option value="Upcoming">Upcoming</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
                    <div class="form-group checkbox-group"><label for="isEmergency">Emergency?</label><input type="checkbox" id="isEmergency"></div>
                    <div class="modal-footer"><button type="submit" class="submit-btn">Save Appointment</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Pet Modal -->
    <div class="modal-overlay" id="add-pet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="pet-modal-title">Add New Pet</h2>
                <button class="close-modal-btn" data-modal-id="add-pet-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pet-form">
                    <input type="hidden" id="petId">
                    <div class="form-group">
                        <label for="clientSelectModal">Client (Owner)</label>
                        <select id="clientSelectModal" required></select>
                    </div>
                    <div class="form-group">
                        <label for="petNameModal">Pet Name</label>
                        <input type="text" id="petNameModal" required>
                    </div>
                    <div class="form-group">
                        <label for="speciesModal">Species</label>
                        <select id="speciesModal" required>
                            <option value="Canine">Canine</option>
                            <option value="Feline">Feline</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="breedModal">Breed</label>
                        <input type="text" id="breedModal">
                    </div>
                    <div class="form-group">
                        <label for="dobModal">Date of Birth</label>
                        <input type="date" id="dobModal">
                    </div>
                    <div class="form-group">
                        <label for="sexModal">Sex</label>
                        <select id="sexModal">
                            <option value=""></option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="firstVisitDateModal">First Visit Date</label>
                        <input type="date" id="firstVisitDateModal" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="submit-btn">Save Pet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit User Modal -->
    <div class="modal-overlay" id="add-user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="user-modal-title">Add New User</h2>
                <button class="close-modal-btn" data-modal-id="add-user-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <div class="form-group"><label for="fullNameModal">Full Name</label><input type="text" id="fullNameModal" required></div>
                    <div class="form-group"><label for="usernameModal">Username</label><input type="text" id="usernameModal" required></div>
                    <div class="form-group"><label for="passwordModal">Password</label><input type="password" id="passwordModal" required></div>
                    <div class="form-group"><label for="roleModal">Role</label><select id="roleModal" required><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
                    <div class="modal-footer"><button type="submit" class="submit-btn">Save User</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Visit Deletion Modal -->
    <div class="modal-overlay" id="confirm-visit-delete-modal">
        <div class="modal-content confirm-modal">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this visit record? This action cannot be undone.</p>
            <div class="modal-footer">
                <button class="join-now secondary-btn" id="cancel-visit-delete-btn">Cancel</button>
                <button class="join-now danger-btn" id="confirm-visit-delete-btn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Confirm Pet Deletion Modal -->
    <div class="modal-overlay" id="confirm-pet-delete-modal">
        <div class="modal-content confirm-modal">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this pet? All associated visit records will also be deleted. This action cannot be undone.</p>
            <div class="modal-footer">
                <button class="join-now secondary-btn" id="cancel-pet-delete-btn">Cancel</button>
                <button class="join-now danger-btn" id="confirm-pet-delete-btn">Delete</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="edit-user-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="edit-user-modal-title">Edit User</h2>
            <button class="close-modal-btn" data-modal-id="edit-user-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-user-form">
                <input type="hidden" id="editUsernameModal">
                <div class="form-group">
                    <label for="editFullNameModal">Full Name</label>
                    <input type="text" id="editFullNameModal" required>
                </div>
                <div class="form-group">
                    <label for="editRoleModal">Role</label>
                    <select id="editRoleModal" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="submit-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <div class="modal-overlay" id="change-password-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button class="close-modal-btn" data-modal-id="change-password-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="change-password-form">
                    <div class="form-group">
                        <label for="oldPasswordModal">Current Password</label>
                        <input type="password" id="oldPasswordModal" required>
                    </div>
                    <div class="form-group">
                        <label for="newPasswordModal">New Password</label>
                        <input type="password" id="newPasswordModal" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="submit-btn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal-overlay" id="add-client-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="client-modal-title">New Client</h2>
                <button class="close-modal-btn" data-modal-id="add-client-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="client-form">
                    <input type="hidden" id="clientId">
                    <div class="form-group">
                        <label for="clientFullNameModal">Full Name</label>
                        <input type="text" id="clientFullNameModal" required>
                    </div>
                    <div class="form-group">
                        <label for="clientPhoneModal">Phone</label>
                        <input type="tel" id="clientPhoneModal">
                    </div>
                    <div class="form-group">
                        <label for="clientEmailModal">Email</label>
                        <input type="email" id="clientEmailModal">
                    </div>
                    <div class="form-group">
                        <label for="clientAddressModal">Address</label>
                        <textarea id="clientAddressModal" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="submit-btn">Save Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="medical-record-modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="medical-record-modal-title">Medical Record</h2>
                <button class="close-modal-btn" data-modal-id="medical-record-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="medical-record-form">
                    <input type="hidden" id="medicalRecordAppointmentId">
                    <div class="form-group">
                        <label>Appointment Date</label>
                        <p id="medicalRecordDate"></p>
                    </div>
                    <div class="form-group">
                        <label for="subjectiveNotes">Subjective (History)</label>
                        <textarea id="subjectiveNotes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="objectiveNotes">Objective (Findings)</label>
                        <textarea id="objectiveNotes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assessmentNotes">Assessment (Diagnosis)</label>
                        <textarea id="assessmentNotes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="planNotes">Plan (Treatment)</label>
                        <textarea id="planNotes" rows="3"></textarea>
                    </div>

                    <h3>Invoice Items</h3>
                    <div class="line-items-container" id="line-items-container"></div>
                    <div class="add-line-item-controls">
                        <select id="line-item-select"></select>
                        <button type="button" class="join-now secondary-btn" id="add-line-item-btn">Add Item</button>
                    </div>
                    <div class="invoice-total">
                        <strong>Total: ₱<span id="invoice-total-amount">0.00</span></strong>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="submit-btn">Save Medical Record & Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="session-timeout-modal">
        <div class="modal-content confirm-modal">
            <h3>Session Timeout</h3>
            <p>Your session is about to expire due to inactivity. You will be logged out in <strong id="timeout-countdown">60</strong> seconds.</p>
            <div class="modal-footer">
                <button class="join-now" id="stay-logged-in-btn">Stay Logged In</button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/chart.min.js"></script>
    <script>
        <?php
            // Load app config
            $appConfig = require 'config/app.php';
        ?>
        // Pass session data from PHP to JavaScript if the user is logged in
        const loggedInUser = <?php echo $is_logged_in ? json_encode($_SESSION['user']) : 'null'; ?>;
        
        // Pass app configuration to JavaScript
        const appSettings = {
            sessionTimeoutSeconds: <?php echo $appConfig['session_timeout_seconds']; ?>,
            sessionWarningSeconds: <?php echo $appConfig['session_warning_seconds']; ?>
        };
    </script>
    <script src="script.js"></script>
</body>
</html>
