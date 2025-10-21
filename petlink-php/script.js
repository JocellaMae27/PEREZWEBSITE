document.addEventListener('DOMContentLoaded', function () {
    let allPatients = [], allVisits = [], allUsers = [], allClients = [];
    let visitsChart = null, visitToDeleteId = null, petToDeleteId = null;
    let csrfToken = null;
    let appointmentsCurrentPage = 1;
    let appointmentsSearchTerm = '';
    let medicalModalData = {};

    const clientsTableBody = document.getElementById('clients-table-body');
    const clientForm = document.getElementById('client-form');

    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const pageTitle = document.getElementById('page-title');
    const loginContainer = document.getElementById('login-container');
    const appContainer = document.getElementById('app-container');
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const logoutBtn = document.getElementById('logout-btn');
    const manageUsersNav = document.getElementById('manage-users-nav');
    const addUserBtn = document.getElementById('add-user-btn');
    const userModal = document.getElementById('add-user-modal');
    const userForm = document.getElementById('user-form');
    const usersTableBody = document.getElementById('users-table-body');
    const navLinks = document.querySelectorAll('.navigation a');
    const pages = document.querySelectorAll('.page-content');
    const todaysVisitsCount = document.getElementById('todays-visits-count');
    const urgentFollowupsCount = document.getElementById('urgent-followups-count');
    const newPatientsCount = document.getElementById('new-patients-count');
    const recentVisitsList = document.getElementById('recent-visits-list');
    const upcomingAppointmentsList = document.getElementById('upcoming-appointments-list');
    const appointmentsTableBody = document.getElementById('appointments-table-body');
    const petRecordsGrid = document.getElementById('pet-records-grid');
    const emergencyOwnerName = document.getElementById('emergency-owner-name');
    const emergencyTime = document.getElementById('emergency-time');
    const emergencyDetails = document.getElementById('emergency-details');
    const scheduleNowBtn = document.getElementById('schedule-now-btn');
    const addAppointmentPageBtn = document.getElementById('add-appointment-btn-page');
    const appointmentModal = document.getElementById('add-appointment-modal');
    const appointmentForm = document.getElementById('appointment-form');
    const patientSelect = document.getElementById('patient-select');
    const statusSelect = document.getElementById('status-select');
    const addPetBtn = document.getElementById('add-pet-btn');
    const petModal = document.getElementById('add-pet-modal');
    const petForm = document.getElementById('pet-form');
    const confirmVisitDeleteModal = document.getElementById('confirm-visit-delete-modal');
    const confirmVisitDeleteBtn = document.getElementById('confirm-visit-delete-btn');
    const confirmPetDeleteModal = document.getElementById('confirm-pet-delete-modal');
    const confirmPetDeleteBtn = document.getElementById('confirm-pet-delete-btn');
    const petRecordsSearch = document.getElementById('pet-records-search');
    const backToRecordsLink = document.getElementById('back-to-records-link');

    let inactivityTimer;
    let countdownInterval;
    const sessionTimeoutSeconds = appSettings.sessionTimeoutSeconds;
    const warningTimeSeconds = appSettings.sessionWarningSeconds;

    function startInactivityTimer() {
        // Clear any existing timers
        clearTimeout(inactivityTimer);
        clearInterval(countdownInterval);

        // Hide the modal in case it was left open
        document.getElementById('session-timeout-modal').classList.remove('active');

        const warningTimerDuration = Math.max(0, (sessionTimeoutSeconds - warningTimeSeconds) * 1000);
        inactivityTimer = setTimeout(showTimeoutWarning, warningTimerDuration);
    }

    function showTimeoutWarning() {
        const modal = document.getElementById('session-timeout-modal');
        const countdownSpan = document.getElementById('timeout-countdown');
        let secondsLeft = warningTimeSeconds;
        
        countdownSpan.textContent = secondsLeft;
        modal.classList.add('active');

        countdownInterval = setInterval(() => {
            secondsLeft--;
            countdownSpan.textContent = secondsLeft;
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                handleLogout(); // Log the user out if the countdown finishes
            }
        }, 1000);
    }

    async function extendSession() {
        try {
            // Ping the server to update the session's last_activity time
            const response = await fetch('api/ping.php');
            if (response.ok) {
                console.log('Session extended.');
                startInactivityTimer(); // Reset the main inactivity timer
            } else {
                // If the ping fails (e.g., session already expired), log out
                handleLogout();
            }
        } catch (error) {
            console.error('Failed to extend session:', error);
            handleLogout();
        }
    }

    // --- END SESSION TIMEOUT LOGIC ---

    function init() {
        if (loggedInUser) {
            showApp(loggedInUser);
            // Start the inactivity timer as soon as the app loads for a logged-in user
            startInactivityTimer(); 
            // Add event listeners that reset the timer on any user activity
            ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => {
                document.addEventListener(event, startInactivityTimer);
            });
        } else {
            showLogin();
        }
    }

    function showLogin() {
        loginContainer.classList.remove('hidden');
        appContainer.classList.add('hidden');
        loginForm.addEventListener('submit', handleLogin);
    }

    function showApp(user) {
        if (loginContainer) loginContainer.classList.add('hidden');
        if (appContainer) appContainer.classList.remove('hidden');
        
        applyRBAC(user);
        loadInitialData();
        attachEventListeners();
    }

    async function handleRestoreFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        const confirmation = confirm(
            `DANGER: Are you absolutely sure you want to restore from the file "${file.name}"?\n\nThis will ERASE all current data and replace it. This action cannot be undone.`
        );

        if (!confirmation) {
            e.target.value = ''; // Reset the file input
            return;
        }

        const formData = new FormData();
        formData.append('backup_file', file);

        try {
            const response = await fetch('api/restore.php', {
                method: 'POST',
                // Do NOT set Content-Type header, browser will do it with FormData
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                alert('Database restored successfully! The application will now reload.');
                location.reload();
            } else {
                alert(`Restore failed: ${result.message}`);
            }
        } catch (error) {
            console.error('Error restoring database:', error);
            alert('An error occurred during the restore process. Check the console for details.');
        } finally {
            e.target.value = ''; // Reset the file input regardless of outcome
        }
    }

    // --- ACTION LOG FUNCTIONS ---
    let actionLogCurrentPage = 1;
    let actionLogSearchTerm = '';

    async function loadAndRenderActionLogs(page = 1, search = '') {
        actionLogCurrentPage = page;
        actionLogSearchTerm = search;
        try {
            const response = await fetch(`api/get_action_logs.php?page=${page}&search=${encodeURIComponent(search)}`);
            const result = await response.json();
            if (result.status === 'success') {
                renderActionLogsTable(result.data.logs);
                renderPaginationControls(result.data.totalPages, result.data.currentPage, 'action-log-pagination', 'action-log');
            }
        } catch (error) {
            console.error('Failed to load action logs:', error);
        }
    }

    function renderActionLogsTable(logs) {
        const tableBody = document.getElementById('action-log-table-body');
        tableBody.innerHTML = '';
        if (!logs.length) {
            tableBody.innerHTML = `<tr><td colspan="5" class="placeholder-text">No log entries found.</td></tr>`;
            return;
        }
        logs.forEach(log => {
            const timestamp = new Date(log.timestamp).toLocaleString();
            tableBody.innerHTML += `
                <tr>
                    <td data-label="Timestamp">${timestamp}</td>
                    <td data-label="User">${log.username}</td>
                    <td data-label="Action">${log.action}</td>
                    <td data-label="Details">${log.details || 'N/A'}</td>
                    <td data-label="IP Address">${log.ip_address}</td>
                </tr>
            `;
        });
    }
    

    function attachEventListeners() {
        menuToggleBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        document.getElementById('clients-search').addEventListener('input', filterClientsTable);
        document.querySelector('.your-projects .see-all').addEventListener('click', (e) => {
            e.preventDefault(); // Prevent the link from trying to navigate to "#"
            navigateToPage('appointments');
        });

        document.getElementById('stay-logged-in-btn').addEventListener('click', extendSession);
        document.getElementById('recycle-bin-content').addEventListener('click', handleRecycleBinAction);

        const restoreFileInput = document.getElementById('restore-file-input');
        if (restoreFileInput) {
            restoreFileInput.addEventListener('change', handleRestoreFileSelect);
        }

        const backupBtn = document.getElementById('backup-db-btn');
        if (backupBtn) {
            backupBtn.addEventListener('click', () => {
                window.location.href = 'api/backup.php';
            });
        }

        document.getElementById('cancel-visit-delete-btn').addEventListener('click', () => {
            confirmVisitDeleteModal.classList.remove('active');
        });

        // Makes the "Cancel" button on the pet delete modal work
        document.getElementById('cancel-pet-delete-btn').addEventListener('click', () => {
            confirmPetDeleteModal.classList.remove('active');
        });

        document.getElementById('add-client-btn-page').addEventListener('click', () => openClientModal());
        clientForm.addEventListener('submit', handleClientFormSubmit);
        clientsTableBody.addEventListener('click', handleClientsTableClick);
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                navigateToPage(link.dataset.target);
                if (window.innerWidth <= 992) closeSidebar();
            });
        });

        document.getElementById('new-visit-btn').addEventListener('click', (e) => {
            // The patient ID is set on this button when the detail page is rendered
            const patientId = e.target.dataset.patientId;
            if (patientId) {
                // Open the modal, passing null (for a new appointment) and the patient's ID
                openAppointmentModal(null, patientId);
            }
        });
        logoutBtn.addEventListener('click', handleLogout);
        
        userForm.addEventListener('submit', handleUserFormSubmit);
        usersTableBody.addEventListener('click', handleUsersTableClick);
        addUserBtn.addEventListener('click', () => openUserModal());
        
        backToRecordsLink.addEventListener('click', (e) => { e.preventDefault(); navigateToPage('records'); });
        
        appointmentForm.addEventListener('submit', handleAppointmentFormSubmit);
        appointmentsTableBody.addEventListener('click', handleAppointmentsTableClick);

        petForm.addEventListener('submit', handlePetFormSubmit);
        petRecordsGrid.addEventListener('click', handlePetRecordsGridClick);
        petRecordsSearch.addEventListener('input', filterPetRecords);

        confirmPetDeleteBtn.addEventListener('click', handleConfirmPetDelete);
        confirmVisitDeleteBtn.addEventListener('click', handleConfirmVisitDelete);
        
        [scheduleNowBtn, addAppointmentPageBtn].forEach(btn => btn.addEventListener('click', () => openAppointmentModal()));
        addPetBtn.addEventListener('click', () => openPetModal());
        
        document.querySelectorAll('.close-modal-btn').forEach(btn => {
            btn.addEventListener('click', () => document.getElementById(btn.dataset.modalId).classList.remove('active'));
        });

        document.getElementById('edit-user-form').addEventListener('submit', handleEditUserFormSubmit);
        document.getElementById('change-password-form').addEventListener('submit', handleChangePasswordFormSubmit);
        document.getElementById('change-password-btn').addEventListener('click', () => {
            document.getElementById('change-password-modal').classList.add('active');
        });

        const appointmentsSearchInput = document.getElementById('appointments-search');
        let searchTimeout;
        appointmentsSearchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchAndRenderAppointments(1, e.target.value);
            }, 500);
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('.pagination-btn') && !e.target.disabled) {
                const pageType = e.target.dataset.type;
                const pageNum = e.target.dataset.page;
                if (pageType === 'appointments') {
                    fetchAndRenderAppointments(pageNum, appointmentsSearchTerm);
                } else if (pageType === 'action-log') {
                    loadAndRenderActionLogs(pageNum, actionLogSearchTerm);
                }
            }
        });
        const actionLogSearchInput = document.getElementById('action-log-search');
        let actionLogSearchTimeout;
        actionLogSearchInput.addEventListener('input', (e) => {
            clearTimeout(actionLogSearchTimeout);
            actionLogSearchTimeout = setTimeout(() => {
                loadAndRenderActionLogs(1, e.target.value);
            }, 500);
        });

        document.getElementById('pet-visit-history-container').addEventListener('click', (e) => {
            if (e.target.matches('.visit-card-header, .visit-card-header *')) {
                const header = e.target.closest('.visit-card-header');
                const body = header.nextElementSibling;
                body.classList.toggle('open');
            } else if (e.target.matches('.edit-record-btn')) {
                const appointmentId = e.target.dataset.appointmentId;
                openMedicalRecordModal(appointmentId);
            }
        });

        document.getElementById('medical-record-form').addEventListener('submit', handleMedicalRecordFormSubmit);
        document.getElementById('add-line-item-btn').addEventListener('click', handleAddLineItem);
        document.getElementById('line-items-container').addEventListener('click', (e) => {
            if (e.target.matches('.remove-line-item-btn')) {
                e.target.closest('.line-item').remove();
                updateInvoiceTotal();
            }
        });
        document.getElementById('line-items-container').addEventListener('input', (e) => {
            if (e.target.matches('input[type="number"]')) {
                updateInvoiceTotal();
            }
        });
    }

    async function handleLogin(e) {
        e.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        if (!username || !password) return;
        
        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                location.reload(); // Reload the page to let PHP handle the session
            } else {
                loginError.textContent = result.message || 'An error occurred.';
                loginError.classList.remove('hidden');
            }
        } catch (error) {
            loginError.textContent = 'Could not connect to the server.';
            loginError.classList.remove('hidden');
        }
    }

    async function handleLogout() {
        await fetch('api/logout.php');
        location.reload();
    }
    
    async function handleUserFormSubmit(e) {
        e.preventDefault();
        const userData = {
            username: document.getElementById('usernameModal').value,
            fullName: document.getElementById('fullNameModal').value,
            password: document.getElementById('passwordModal').value,
            role: document.getElementById('roleModal').value
        };

        if (!userData.username || !userData.password) {
            alert("Username and password are required.");
            return;
        }

        try {
            const response = await fetch('api/users.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(userData)
            });

            if (response.ok) {
                userModal.classList.remove('active');
                loadAppData();
            } else {
                const result = await response.json();
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error saving user:', error);
            alert('An error occurred while saving the user.');
        }
    }
    
    async function handleUsersTableClick(e) {
        const editBtn = e.target.closest('.edit-user-btn');
        const deleteBtn = e.target.closest('.delete-user-btn');

        if (editBtn) {
            const userId = editBtn.dataset.id;
            openEditUserModal(userId);
        }

        if (deleteBtn) {
            const userId = deleteBtn.dataset.id;
            if (userId === loggedInUser.username) {
                alert("You cannot delete your own account.");
                return;
            }
            if (confirm(`Are you sure you want to delete user: ${userId}?`)) {
                try {
                    const response = await fetch(`api/users.php?id=${userId}`, { 
                        method: 'DELETE',
                        headers: { 'X-CSRF-Token': csrfToken }
                    });
                    if (response.ok) {
                        loadAppData();
                    } else {
                        const result = await response.json();
                        alert(`Error: ${result.message}`);
                    }
                } catch (error) {
                    alert('An error occurred while deleting the user.');
                }
            }
        }
    }

    async function handleAppointmentFormSubmit(e) {
        e.preventDefault();
        const appointmentId = document.getElementById('appointmentId').value;
        const appointmentData = {
            patientId: patientSelect.value,
            status: statusSelect.value,
            service: document.getElementById('service').value,
            date: document.getElementById('appointmentDate').value,
            isEmergency: document.getElementById('isEmergency').checked
        };

        let url = 'api/appointments.php';
        let method = 'POST';

        if (appointmentId) {
            appointmentData.id = appointmentId;
            method = 'PUT';
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(appointmentData)
            });
            if (response.ok) {
                appointmentModal.classList.remove('active');
                loadAppData();
            } else {
                alert('Failed to save appointment.');
            }
        } catch (error) {
            alert('An error occurred while saving the appointment.');
        }
    }

    async function handleAppointmentsTableClick(e) {
        const target = e.target;
        const visitId = target.closest('[data-id]')?.dataset.id;
        if (!visitId) return;

        if (target.closest('.edit-btn')) {
            openAppointmentModal(visitId);
        } else if (target.closest('.delete-btn')) {
            visitToDeleteId = visitId;
            confirmVisitDeleteModal.classList.add('active');
        } else if (target.classList.contains('mark-paid-btn')) {
            const amount = prompt("Enter amount paid:", "1000");
            if (amount !== null) {
                const updateData = { id: visitId, paymentStatus: 'Paid', amount: parseFloat(amount) || 0 };
                await fetch('api/appointments.php', {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(updateData)
                }).then(res => res.ok && loadAppData());
            }
        } else if (target.classList.contains('quick-status-select')) {
            const newStatus = target.value;
            const updateData = { id: visitId, status: newStatus };
            if (newStatus === 'Completed' && allVisits.find(v => v.id == visitId)?.status !== 'Completed') {
                updateData.paymentStatus = 'Pending Payment';
            }
            await fetch('api/appointments.php', {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(updateData)
            }).then(res => res.ok && loadAppData());
        }
    }
    
    async function handlePetFormSubmit(e) {
        e.preventDefault();
        const petId = document.getElementById('petId').value;
        const petData = {
            clientId: document.getElementById('clientSelectModal').value,
            petName: document.getElementById('petNameModal').value,
            species: document.getElementById('speciesModal').value,
            breed: document.getElementById('breedModal').value,
            dob: document.getElementById('dobModal').value,
            sex: document.getElementById('sexModal').value,
            firstVisitDate: document.getElementById('firstVisitDateModal').value
        };

        let url = 'api/patients.php';
        let method = 'POST';

        if (petId) {
            petData.id = petId;
            method = 'PUT';
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(petData)
            });
            if (response.ok) {
                petModal.classList.remove('active');
                loadAppData();
            } else {
                alert('Failed to save pet record.');
            }
        } catch (error) {
            alert('An error occurred while saving.');
        }
    }

    function handlePetRecordsGridClick(e) {
        const card = e.target.closest('.pet-record-card');
        if (!card) return;
        const petId = card.dataset.id;

        if (e.target.closest('.edit-pet-btn')) {
            openPetModal(petId);
        } else if (e.target.closest('.delete-pet-btn')) {
            petToDeleteId = petId;
            confirmPetDeleteModal.classList.add('active');
        } else {
            navigateToPage('pet-detail', petId);
        }
    }

    async function handleConfirmPetDelete() {
        if (!petToDeleteId) return;
        try {
            const response = await fetch(`api/patients.php?id=${petToDeleteId}`, { 
                method: 'DELETE',
                headers: { 'X-CSRF-Token': csrfToken }
            });
            if (response.ok) {
                petToDeleteId = null;
                confirmPetDeleteModal.classList.remove('active');
                navigateToPage('records');
                loadAppData();
            } else {
                alert('Failed to delete pet.');
            }
        } catch (error) {
            alert('An error occurred while deleting.');
        }
    }

    async function handleConfirmVisitDelete() {
    if (!visitToDeleteId) return;
    try {
        const response = await fetch(`api/appointments.php?id=${visitToDeleteId}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-Token': csrfToken }
        });
        if (response.ok) {
            visitToDeleteId = null;
            confirmVisitDeleteModal.classList.remove('active');
            loadAppData();
        } else {
            alert('Failed to delete visit.');
        }
    } catch (error) {
        alert('An error occurred while deleting visit.');
    }
}

    // --- Data Loading ---
    async function loadAppData() {
    try {
        const response = await fetch('api/get_data.php');
        if (response.status === 401) {
            handleLogout();
            return;
        }
        const result = await response.json();

        if (result.status === 'success') {
            csrfToken = result.data.csrfToken;
            allUsers = result.data.users;
            allPatients = result.data.patients;
            allVisits = result.data.visits;
            allClients = result.data.clients;

            renderUsersTable(allUsers);
            renderPetRecords(allPatients);
            populatePatientSelect(allPatients);
            populateClientSelect(allClients);
            updateDashboard(allVisits, allPatients);
            fetchAndRenderAppointments();
            renderReportsPage(allVisits, allPatients);
            renderPetDetailPageFromCurrentState();
        } else {
            console.error('Failed to load app data:', result.message);
        }
    } catch (error) {
        console.error('Error fetching app data:', error);
    }
}

    // --- UI and Data Rendering (UNCHANGED FROM ORIGINAL) ---
    
    function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('active'); }
    function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('active'); }
    function populateClientSelect(clients) {
        const clientSelect = document.getElementById('clientSelectModal');
        if(!clientSelect) return;
        clientSelect.innerHTML = '<option value="" disabled selected>Choose a client...</option>';
        clients.sort((a, b) => a.full_name.localeCompare(b.full_name)).forEach(c => { 
            clientSelect.innerHTML += `<option value="${c.id}">${c.full_name}</option>`; 
        });
    }
    function renderClientsTable(clients) {
        if (!clientsTableBody) return;
        clientsTableBody.innerHTML = '';
        if (!clients.length) {
            clientsTableBody.innerHTML = `<tr><td colspan="4" class="placeholder-text">No clients found.</td></tr>`;
            return;
        }
        clients.forEach(client => {
            clientsTableBody.innerHTML += `
                <tr>
                    <td data-label="Full Name">${client.full_name}</td>
                    <td data-label="Phone">${client.phone || 'N/A'}</td>
                    <td data-label="Email">${client.email || 'N/A'}</td>
                    <td data-label="Actions" class="action-buttons">
                        <button class="action-btn edit-client-btn" data-id="${client.id}"><i class='bx bxs-edit'></i></button>
                        <button class="action-btn delete-client-btn" data-id="${client.id}"><i class='bx bxs-trash'></i></button>
                    </td>
                </tr>`;
        });
    }
    function applyRBAC(user) {
        document.getElementById('sidebar-user-name').textContent = user.fullName;
        document.getElementById('sidebar-user-role').textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
        const isAdmin = user.role === 'admin';
        manageUsersNav.classList.toggle('hidden', !isAdmin);
        document.getElementById('recycle-bin-nav').classList.toggle('hidden', !isAdmin);
        document.getElementById('action-log-nav').classList.toggle('hidden', !isAdmin);
    }

    function navigateToPage(targetId, param = null) {
        pages.forEach(page => page.classList.remove('active'));
        const targetPage = document.querySelector(`[data-page="${targetId}"]`);
        if (targetPage) targetPage.classList.add('active');

        let newTitle = 'Dashboard';
        navLinks.forEach(link => {
            const linkTarget = link.dataset.target;
            const isCurrentPage = linkTarget === targetId;
            const isParentPage = targetId === 'pet-detail' && linkTarget === 'records';
            link.parentElement.classList.toggle('active', isCurrentPage || isParentPage);
            if (isCurrentPage) newTitle = link.dataset.title || 'Dashboard';
        });
        
        if(pageTitle) pageTitle.textContent = newTitle;

        if (targetId === 'pet-detail' && param) {
            sessionStorage.setItem('currentPetDetailId', param);
            if(pageTitle) pageTitle.textContent = "Pet Details";
            renderPetDetailPage(param);
        }

        if (targetId === 'recycle-bin') {
            loadAndRenderRecycleBin();
        }

        if (targetId === 'action-log') {
            loadAndRenderActionLogs();
        }
    }

    async function loadAndRenderRecycleBin() {
        try {
            const response = await fetch('api/get_deleted_items.php');
            const result = await response.json();
            if (result.status === 'success') {
                renderRecycleBin(result.data);
            }
        } catch (error) {
            console.error('Failed to load recycle bin items:', error);
        }
    }

    function renderRecycleBin(data) {
        const container = document.getElementById('recycle-bin-content');
        container.innerHTML = '';
        let content = '';

        const itemTypes = {
            clients: 'Clients',
            patients: 'Patients',
            appointments: 'Appointments',
            users: 'Users'
        };

        for (const type in itemTypes) {
            content += `<h2>Deleted ${itemTypes[type]}</h2>`;
            const items = data[type];
            if (items.length === 0) {
                content += '<p class="placeholder-text">No deleted items of this type.</p>';
            } else {
                content += `
                    <div class="table-container">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Name / Identifier</th>
                                    <th>Date Deleted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                items.forEach(item => {
                    const identifier = item.full_name || item.pet_name || item.id;
                    const deletedDate = new Date(item.deleted_at).toLocaleString();
                    content += `
                        <tr>
                            <td data-label="Identifier">${identifier}</td>
                            <td data-label="Date Deleted">${deletedDate}</td>
                            <td data-label="Actions" class="action-buttons">
                                <button class="join-now secondary-btn restore-btn" data-type="${type}" data-id="${item.id}">Restore</button>
                                <button class="join-now danger-btn force-delete-btn" data-type="${type}" data-id="${item.id}">Delete Permanently</button>
                            </td>
                        </tr>
                    `;
                });
                content += '</tbody></table></div>';
            }
        }
        container.innerHTML = content;
    }

    async function handleRecycleBinAction(e) {
        const restoreBtn = e.target.closest('.restore-btn');
        const forceDeleteBtn = e.target.closest('.force-delete-btn');
        
        if (!restoreBtn && !forceDeleteBtn) return;
        
        const button = restoreBtn || forceDeleteBtn;
        const action = restoreBtn ? 'restore' : 'force_delete';
        const type = button.dataset.type;
        const id = button.dataset.id;
        
        let confirmMessage = `Are you sure you want to ${action.replace('_', ' ')} this item?`;
        if(action === 'force_delete') {
            confirmMessage += '\n\nThis action is permanent and cannot be undone.';
        }

        if (confirm(confirmMessage)) {
            try {
                const response = await fetch('api/recycle_bin_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, type, id })
                });
                if (response.ok) {
                    loadAndRenderRecycleBin(); // Refresh the list
                } else {
                    alert('Action failed. Please try again.');
                }
            } catch (error) {
                console.error('Recycle bin action failed:', error);
            }
        }
    }

    function renderUsersTable(users) {
        if (!usersTableBody) return;
        usersTableBody.innerHTML = '';
        users.forEach(user => {
            usersTableBody.innerHTML += `
                <tr>
                    <td data-label="Full Name">${user.full_name}</td>
                    <td data-label="Username">${user.id}</td>
                    <td data-label="Role">${user.role}</td>
                    <td data-label="Actions" class="action-buttons">
                        <button class="action-btn edit-user-btn" data-id="${user.id}"><i class='bx bxs-edit'></i></button>
                        <button class="action-btn delete-user-btn" data-id="${user.id}"><i class='bx bxs-trash'></i></button>
                    </td>
                </tr>`;
        });
    }

    function updateDashboard(visits, patients) {
        const today = new Date().toISOString().slice(0, 10);
        
        const todaysVisits = visits.filter(v => {
            const appointmentDate = v.date.slice(0, 10);
            const completionDate = v.completion_date;
            return (appointmentDate === today && v.status !== 'Cancelled') || completionDate === today;
        }).length;
        todaysVisitsCount.textContent = todaysVisits;
        
        urgentFollowupsCount.textContent = visits.filter(v => v.status !== 'Completed' && v.isEmergency).length;
        const startOfMonth = new Date(); startOfMonth.setDate(1);
        newPatientsCount.textContent = patients.filter(p => new Date(p.firstVisitDate) >= startOfMonth).length;
        
        recentVisitsList.innerHTML = '';
        const recentCompleted = visits.filter(v => v.status === 'Completed').sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 2);
        if (recentCompleted.length === 0) { 
            recentVisitsList.innerHTML = `<p class="placeholder-text">No recent visits found.</p>`; 
        } else {
            recentCompleted.forEach(v => { 
                recentVisitsList.innerHTML += `<div class="visit-item"><div class="visit-info"><div><p class="pet-name">${v.patient.petName} (${v.patient.ownerName})</p><p class="clinic-name">${v.service}</p></div></div><span class="status paid">Paid</span><span class="amount">₱${Number(v.amount || 0).toFixed(2)}</span></div>`; 
            });
        }

        upcomingAppointmentsList.innerHTML = '';
        const upcoming = visits.filter(v => v.status === 'Upcoming').sort((a, b) => new Date(a.date) - new Date(b.date)).slice(0, 2);
        if (upcoming.length === 0) { 
            upcomingAppointmentsList.innerHTML = `<p class="placeholder-text">No upcoming appointments.</p>`; 
        } else {
            upcoming.forEach(v => {
                const formattedDate = new Date(v.date).toLocaleString('en-US', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });
                upcomingAppointmentsList.innerHTML += `<div class="project-item"><div><p class="project-title">${v.service} for '${v.patient.petName}'</p><p class="project-deadline">${formattedDate}</p></div></div>`;
            });
        }

        const latestEmergency = visits.filter(v => v.isEmergency && v.status !== 'Completed').sort((a, b) => new Date(b.date) - new Date(a.date))[0];
        if (latestEmergency) {
            emergencyOwnerName.textContent = latestEmergency.patient.ownerName;
            emergencyTime.textContent = `Arrived ${new Date(latestEmergency.date).toLocaleTimeString()}`;
            emergencyDetails.textContent = `Emergency: '${latestEmergency.patient.petName}' for ${latestEmergency.service}.`;
        } else {
            emergencyOwnerName.textContent = 'No Emergencies'; 
            emergencyTime.textContent = 'Queue is clear'; 
            emergencyDetails.textContent = 'Waiting for next walk-in patient.';
        }
    }

    function renderAppointmentsTable(visits) {
        if (!appointmentsTableBody) return;
        appointmentsTableBody.innerHTML = '';
        if (!visits.length) { 
            appointmentsTableBody.innerHTML = `<tr><td colspan="6" class="placeholder-text">No appointments found.</td></tr>`; 
            return; 
        }
        visits.forEach(v => {
            let quickActions = '—';
            if (v.status === 'Upcoming' || v.status === 'Pending') { 
                quickActions = `<select class="quick-status-select" data-id="${v.id}"><option value="${v.status}" selected>${v.status}</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select>`; 
            } else if (v.status === 'Completed' && v.paymentStatus !== 'Paid') { 
                quickActions = `<button class="join-now secondary-btn mark-paid-btn" data-id="${v.id}">Mark Paid</button>`; 
            }

            const standardActions = `<div class="standard-actions">
                <button class="action-btn edit-btn"><i class='bx bxs-edit'></i></button>
                <button class="action-btn delete-btn"><i class='bx bxs-trash'></i></button>
            </div>`;
            
            const patientName = `${v.patient.petName} <span class="text-gray">(${v.patient.ownerName})</span>`;
            const formattedDate = new Date(v.date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            const paymentStatusClass = v.paymentStatus === 'Paid' ? 'paid' : (v.status === 'Completed' ? 'late' : '');
            const paymentStatusText = v.paymentStatus === 'N/A' ? '—' : v.paymentStatus;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td data-label="Patient">${patientName}</td>
                <td data-label="Service">${v.service}</td>
                <td data-label="Date & Time">${formattedDate}</td>
                <td data-label="Status"><span class="status-badge ${v.status.toLowerCase()}">${v.status}</span></td>
                <td data-label="Payment"><span class="status ${paymentStatusClass}">${paymentStatusText}</span></td>
                <td data-label="Actions" class="quick-actions" data-id="${v.id}">${quickActions} ${standardActions}</td>
            `;
            appointmentsTableBody.appendChild(row);
        });
    }
    
    function renderPetRecords(patients) {
        if (!petRecordsGrid) return;
        petRecordsGrid.innerHTML = '';
        if (!patients.length) { 
            petRecordsGrid.innerHTML = `<p class="placeholder-text">No pet records found.</p>`; 
            return; 
        }
        patients.forEach(p => {
            const speciesIcon = p.species === 'Canine' ? 'bxs-dog' : (p.species === 'Feline' ? 'bxs-cat' : 'bxs-bug');
            petRecordsGrid.innerHTML += `
                <div class="pet-record-card" data-search-term="${p.petName.toLowerCase()} ${p.ownerName.toLowerCase()}" data-id="${p.id}">
                    <div class="pet-card-header">
                        <i class='bx ${speciesIcon}'></i>
                        <div>
                            <h3>${p.petName}</h3>
                            <p>${p.species}</p>
                        </div>
                    </div>
                    <div class="pet-card-body">
                        <p><strong>Owner:</strong> ${p.ownerName}</p>
                    </div>
                    <div class="pet-card-footer action-buttons">
                        <button class="action-btn edit-pet-btn"><i class='bx bxs-edit'></i></button>
                        <button class="action-btn delete-pet-btn"><i class='bx bxs-trash'></i></button>
                    </div>
                </div>`;
        });
        filterPetRecords();
    }
    
    function renderPetDetailPage(patientId) {
        const patient = allPatients.find(p => p.id == patientId);
        if (!patient) {
            navigateToPage('records'); // Go back if patient not found
            return;
        }

        // Populate patient header info
        document.getElementById('pet-detail-icon').className = `bx ${patient.species === 'Canine' ? 'bxs-dog' : (patient.species === 'Feline' ? 'bxs-cat' : 'bxs-bug')}`;
        document.getElementById('pet-detail-name').textContent = patient.petName;
        document.getElementById('pet-detail-owner').textContent = `Owner: ${patient.ownerName}`;
        document.getElementById('pet-detail-species').textContent = patient.species;
        document.getElementById('pet-detail-breed').textContent = patient.breed || 'N/A';
        document.getElementById('pet-detail-sex').textContent = patient.sex || 'N/A';
        document.getElementById('pet-detail-dob').textContent = patient.dob ? new Date(patient.dob).toLocaleDateString() : 'N/A';
        
        // Set the patientId for the "New Visit Record" button
        document.getElementById('new-visit-btn').dataset.patientId = patientId;


        const visitHistoryContainer = document.getElementById('pet-visit-history-container');
        visitHistoryContainer.innerHTML = ''; // Clear previous content

        const patientVisits = allVisits.filter(v => v.patientId == patientId).sort((a, b) => new Date(b.date) - new Date(a.date));
        
        if (!patientVisits.length) {
            visitHistoryContainer.innerHTML = `<p class="placeholder-text">No visit history found for this pet.</p>`;
            return;
        }

        let visitHistoryHTML = '';
        patientVisits.forEach(v => {
            const formattedDate = new Date(v.date).toLocaleString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            
            // Use placeholder text if notes are null or empty
            const subjective = v.subjective || 'No notes recorded.';
            const objective = v.objective || 'No notes recorded.';
            const assessment = v.assessment || 'No notes recorded.';
            const plan = v.plan || 'No notes recorded.';

            visitHistoryHTML += `
                <div class="visit-card">
                    <div class="visit-card-header">
                        <span>${formattedDate} - <strong>${v.service}</strong></span>
                        <button class="join-now secondary-btn edit-record-btn" data-appointment-id="${v.id}">View/Edit Record</button>
                    </div>
                    <div class="visit-card-body">
                        <h4>Subjective (History)</h4>
                        <p>${subjective}</p>
                        <h4>Objective (Findings)</h4>
                        <p>${objective}</p>
                        <h4>Assessment (Diagnosis)</h4>
                        <p>${assessment}</p>
                        <h4>Plan (Treatment)</h4>
                        <p>${plan}</p>
                    </div>
                </div>`;
        });

        visitHistoryContainer.innerHTML = visitHistoryHTML;
    }

    function renderPetDetailPageFromCurrentState() {
        if(document.querySelector('[data-page="pet-detail"].active')) {
            const currentPetId = sessionStorage.getItem('currentPetDetailId');
            if (currentPetId) {
                renderPetDetailPage(currentPetId);
            }
        }
    }

    function renderReportsPage(visits, patients) {
        const chartCanvas = document.getElementById('visits-chart');
        if(!chartCanvas) return;

        document.getElementById('total-patients-kpi').textContent = patients.length;
        document.getElementById('total-visits-kpi').textContent = visits.length;
        document.getElementById('canine-patients-kpi').textContent = patients.filter(p => p.species === 'Canine').length;
        document.getElementById('feline-patients-kpi').textContent = patients.filter(p => p.species === 'Feline').length;
        const last7Days = [...Array(7)].map((_, i) => { const d = new Date(); d.setDate(d.getDate() - i); return d.toISOString().slice(0, 10); }).reverse();
        const visitsByDay = last7Days.map(day => ({ day, count: visits.filter(v => v.date.startsWith(day)).length }));
        const ctx = chartCanvas.getContext('2d');
        if (visitsChart) { visitsChart.destroy(); }
        visitsChart = new Chart(ctx, { type: 'line', data: { labels: visitsByDay.map(d => new Date(d.day).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })), datasets: [{ label: 'Visits', data: visitsByDay.map(d => d.count), borderColor: '#6a6cff', backgroundColor: 'rgba(106, 108, 255, 0.1)', tension: 0.4, fill: true }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
    }

    function populatePatientSelect(patients) {
        if(!patientSelect) return;
        patientSelect.innerHTML = '<option value="" disabled selected>Choose a pet...</option>';
        patients.sort((a, b) => a.petName.localeCompare(b.petName)).forEach(p => { patientSelect.innerHTML += `<option value="${p.id}">${p.petName} (${p.ownerName})</option>`; });
    }

    function filterPetRecords() {
        if (!petRecordsSearch) return;
        const searchTerm = petRecordsSearch.value.toLowerCase();
        document.querySelectorAll('.pet-record-card').forEach(card => { card.style.display = card.dataset.searchTerm.includes(searchTerm) ? '' : 'none'; });
    }

    function filterClientsTable() {
    const searchTerm = document.getElementById('clients-search').value.toLowerCase();
    const rows = document.querySelectorAll('#clients-table-body tr');
    let hasVisibleRows = false;

    rows.forEach(row => {
        // Don't try to filter the "No clients found" placeholder row
        if (row.querySelector('td[colspan="4"]')) {
            return;
        }
        const rowText = row.textContent.toLowerCase();
        const isVisible = rowText.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) {
            hasVisibleRows = true;
        }
    });

    // Optional: Show a message if the search yields no results
    const noResultsRow = document.getElementById('no-clients-search-result');
        if (!hasVisibleRows && searchTerm) {
            if (!noResultsRow) {
                clientsTableBody.insertAdjacentHTML('beforeend', '<tr id="no-clients-search-result"><td colspan="4" class="placeholder-text">No clients match your search.</td></tr>');
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }
    
    // --- Modal Functions (UNCHANGED FROM ORIGINAL) ---
    function openAppointmentModal(visitId = null, preselectedPatientId = null) {
        appointmentForm.reset();
        const visit = allVisits.find(v => v.id == visitId);
        document.getElementById('appointmentId').value = visitId || '';
        document.getElementById('appointment-modal-title').textContent = visit ? 'Edit Appointment' : 'New Appointment';
        
        if (visit) {
            // Logic for editing an existing appointment
            patientSelect.value = visit.patientId;
            statusSelect.value = visit.status;
            document.getElementById('service').value = visit.service;
            document.getElementById('appointmentDate').value = visit.date ? visit.date.replace(' ', 'T') : '';
            document.getElementById('isEmergency').checked = visit.isEmergency;
        } else {
            // Logic for creating a new appointment
            statusSelect.value = 'Upcoming';
            if (preselectedPatientId) {
                // Pre-select the patient if an ID was passed
                patientSelect.value = preselectedPatientId;
            }
        }
        appointmentModal.classList.add('active');
    }

    function openPetModal(petId = null) {
        petForm.reset();
        const pet = allPatients.find(p => p.id == petId);
        document.getElementById('petId').value = petId || '';
        document.getElementById('pet-modal-title').textContent = pet ? 'Edit Pet Record' : 'Add New Pet';
        
        if (pet) { 
            document.getElementById('clientSelectModal').value = pet.clientId;
            document.getElementById('petNameModal').value = pet.petName; 
            document.getElementById('speciesModal').value = pet.species; 
            document.getElementById('breedModal').value = pet.breed;
            document.getElementById('dobModal').value = pet.dob;
            document.getElementById('sexModal').value = pet.sex;
            document.getElementById('firstVisitDateModal').value = pet.firstVisitDate; 
        }
        petModal.classList.add('active');
    }

    function openUserModal() {
        userForm.reset();
        document.getElementById('user-modal-title').textContent = 'New User';
        userModal.classList.add('active');
    }

    function openEditUserModal(userId) {
        const user = allUsers.find(u => u.id === userId);
        if (!user) return;
        
        document.getElementById('editUsernameModal').value = user.id;
        document.getElementById('editFullNameModal').value = user.full_name;
        document.getElementById('editRoleModal').value = user.role;
        
        document.getElementById('edit-user-modal').classList.add('active');
    }

    async function handleEditUserFormSubmit(e) {
        e.preventDefault();
        const userData = {
            type: 'updateInfo',
            username: document.getElementById('editUsernameModal').value,
            fullName: document.getElementById('editFullNameModal').value,
            role: document.getElementById('editRoleModal').value
        };

        try {
            const response = await fetch('api/users.php', {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken 
                },
                body: JSON.stringify(userData)
            });

            if (response.ok) {
                document.getElementById('edit-user-modal').classList.remove('active');
                loadAppData();
            } else {
                const result = await response.json();
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert('An error occurred while updating the user.');
        }
    }

    async function handleChangePasswordFormSubmit(e) {
        e.preventDefault();
        const passwordData = {
            type: 'changePassword',
            username: loggedInUser.username,
            oldPassword: document.getElementById('oldPasswordModal').value,
            newPassword: document.getElementById('newPasswordModal').value
        };

        try {
            const response = await fetch('api/users.php', {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken 
                },
                body: JSON.stringify(passwordData)
            });
            
            const modal = document.getElementById('change-password-modal');
            if (response.ok) {
                modal.classList.remove('active');
                alert('Password changed successfully.');
                e.target.reset();
            } else {
                const result = await response.json();
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert('An error occurred while changing the password.');
        }
    }

    async function loadInitialData() {
        try {
            const response = await fetch('api/get_data.php');
            if (response.status === 401) { handleLogout(); return; }
            const result = await response.json();

            if (result.status === 'success') {
                csrfToken = result.data.csrfToken;
                allUsers = result.data.users;
                allPatients = result.data.patients;
                allVisits = result.data.visits;
                allClients = result.data.clients;

                renderUsersTable(allUsers);
                renderClientsTable(allClients);
                renderPetRecords(allPatients);
                populatePatientSelect(allPatients);
                populateClientSelect(allClients);
                updateDashboard(allVisits, allPatients);
                renderReportsPage(allVisits, allPatients);
                renderPetDetailPageFromCurrentState();
                fetchAndRenderAppointments();
            } else {
                console.error('Failed to load app data:', result.message);
            }
        } catch (error) {
            console.error('Error fetching app data:', error);
        }
    }
    
    async function fetchAndRenderAppointments(page = 1, search = '') {
        try {
            const response = await fetch(`api/get_appointments.php?page=${page}&search=${encodeURIComponent(search)}`);
            const result = await response.json();
            if (result.status === 'success') {
                renderAppointmentsTable(result.data.appointments);
                renderPaginationControls(result.data.totalPages, result.data.currentPage, 'appointments-pagination', 'appointments'); 
                appointmentsCurrentPage = result.data.currentPage;
                appointmentsSearchTerm = search;
            }
        } catch (error) {
            console.error('Failed to fetch appointments:', error);
        }
    }

    function renderPaginationControls(totalPages, currentPage, containerId, pageType) {
        const paginationContainer = document.getElementById(containerId);
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        if (totalPages <= 1) return;

        currentPage = parseInt(currentPage);
        totalPages = parseInt(totalPages);

        let html = `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" data-type="${pageType}">Prev</button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}" data-type="${pageType}">${i}</button>`;
        }
        html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" data-type="${pageType}">Next</button>`;
        
        paginationContainer.innerHTML = html;
    }
    
    async function openMedicalRecordModal(appointmentId) {
        const appointment = allVisits.find(v => v.id == appointmentId);
        if (!appointment) return;

        document.getElementById('medicalRecordAppointmentId').value = appointmentId;
        document.getElementById('medicalRecordDate').textContent = new Date(appointment.date).toLocaleString();
        
        try {
            const response = await fetch(`api/get_medical_data.php?appointment_id=${appointmentId}`);
            const result = await response.json();
            if (result.status === 'success') {
                medicalModalData = result.data;
                const { record, services, inventory, lineItems } = medicalModalData;
                
                document.getElementById('subjectiveNotes').value = record?.subjective || '';
                document.getElementById('objectiveNotes').value = record?.objective || '';
                document.getElementById('assessmentNotes').value = record?.assessment || '';
                document.getElementById('planNotes').value = record?.plan || '';

                const itemSelect = document.getElementById('line-item-select');
                itemSelect.innerHTML = '<optgroup label="Services">';
                services.forEach(s => itemSelect.innerHTML += `<option value="service-${s.id}" data-price="${s.price}">${s.name}</option>`);
                itemSelect.innerHTML += '</optgroup><optgroup label="Inventory Items">';
                inventory.forEach(i => itemSelect.innerHTML += `<option value="inventory-${i.id}" data-price="${i.price}">${i.name} (Stock: ${i.stock})</option>`);
                itemSelect.innerHTML += '</optgroup>';

                const lineItemsContainer = document.getElementById('line-items-container');
                lineItemsContainer.innerHTML = '';
                lineItems.forEach(item => {
                    const fullItem = item.item_type === 'service' 
                        ? services.find(s => s.id == item.item_id)
                        : inventory.find(i => i.id == item.item_id);
                    
                    if(fullItem) addLineItemToDOM(item.item_type, item.item_id, fullItem.name, item.price_at_time, item.quantity);
                });
                
                updateInvoiceTotal();
                document.getElementById('medical-record-modal').classList.add('active');
            }
        } catch (error) {
            console.error('Error fetching medical data:', error);
        }
    }

    function handleAddLineItem() {
        const select = document.getElementById('line-item-select');
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption) return;

        const [type, id] = selectedOption.value.split('-');
        const name = selectedOption.textContent;
        const price = selectedOption.dataset.price;
        
        addLineItemToDOM(type, id, name, price, 1);
        updateInvoiceTotal();
    }

    function addLineItemToDOM(type, id, name, price, quantity) {
        const container = document.getElementById('line-items-container');
        const existingItem = container.querySelector(`[data-item-id="${type}-${id}"]`);
        if (existingItem) {
            const qtyInput = existingItem.querySelector('input[type="number"]');
            qtyInput.value = parseInt(qtyInput.value) + 1;
            return;
        }

        const itemHTML = `
            <div class="line-item" data-item-id="${type}-${id}" data-item-type="${type}" data-price="${price}">
                <span class="line-item-name">${name}</span>
                <span class="line-item-price">₱${parseFloat(price).toFixed(2)}</span>
                <input type="number" value="${quantity}" min="1" class="form-control">
                <button type="button" class="action-btn remove-line-item-btn">&times;</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', itemHTML);
    }

    function updateInvoiceTotal() {
        let total = 0;
        document.querySelectorAll('#line-items-container .line-item').forEach(item => {
            const price = parseFloat(item.dataset.price);
            const quantity = parseInt(item.querySelector('input').value);
            total += price * quantity;
        });
        document.getElementById('invoice-total-amount').textContent = total.toFixed(2);
    }
    
    async function handleMedicalRecordFormSubmit(e) {
        e.preventDefault();
        
        const lineItems = [];
        document.querySelectorAll('#line-items-container .line-item').forEach(item => {
            const [type, id] = item.dataset.itemId.split('-');
            lineItems.push({
                item_type: type,
                item_id: id,
                quantity: item.querySelector('input').value,
                price_at_time: item.dataset.price
            });
        });

        const submissionData = {
            appointmentId: document.getElementById('medicalRecordAppointmentId').value,
            record: {
                subjective: document.getElementById('subjectiveNotes').value,
                objective: document.getElementById('objectiveNotes').value,
                assessment: document.getElementById('assessmentNotes').value,
                plan: document.getElementById('planNotes').value,
            },
            lineItems: lineItems
        };

        try {
            const response = await fetch('api/medical_records.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(submissionData)
            });
            if (response.ok) {
                document.getElementById('medical-record-modal').classList.remove('active');
                await loadInitialData();
            } else {
                alert('Failed to save medical record.');
            }
        } catch (error) {
            alert('An error occurred while saving.');
        }
    }

    function openClientModal(clientId = null) {
    clientForm.reset();
    const clientModal = document.getElementById('add-client-modal');
    const client = allClients.find(c => c.id == clientId);

    document.getElementById('clientId').value = clientId || '';
    document.getElementById('client-modal-title').textContent = client ? 'Edit Client' : 'New Client';
    
    if (client) {
        document.getElementById('clientFullNameModal').value = client.full_name;
        document.getElementById('clientPhoneModal').value = client.phone;
        document.getElementById('clientEmailModal').value = client.email;
        document.getElementById('clientAddressModal').value = client.address;
    }
    clientModal.classList.add('active');
}

async function handleClientFormSubmit(e) {
    e.preventDefault();
    const clientId = document.getElementById('clientId').value;
    const clientData = {
        fullName: document.getElementById('clientFullNameModal').value,
        phone: document.getElementById('clientPhoneModal').value,
        email: document.getElementById('clientEmailModal').value,
        address: document.getElementById('clientAddressModal').value,
    };

    let url = 'api/clients.php';
    let method = 'POST';

    if (clientId) {
        clientData.id = clientId;
        method = 'PUT';
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(clientData)
        });
        if (response.ok) {
            document.getElementById('add-client-modal').classList.remove('active');
            await loadInitialData(); // Reload all data to see changes everywhere
        } else {
            alert('Failed to save client.');
        }
    } catch (error) {
        alert('An error occurred while saving the client.');
    }
}

async function handleClientsTableClick(e) {
    const editBtn = e.target.closest('.edit-client-btn');
    const deleteBtn = e.target.closest('.delete-client-btn');

    if (editBtn) {
        openClientModal(editBtn.dataset.id);
    }

    if (deleteBtn) {
        const clientId = deleteBtn.dataset.id;
        if (confirm('Are you sure you want to delete this client? This will also delete all of their pets and associated appointments.')) {
            try {
                const response = await fetch(`api/clients.php?id=${clientId}`, { 
                    method: 'DELETE',
                    headers: { 'X-CSRF-Token': csrfToken }
                });
                if (response.ok) {
                    await loadInitialData();
                } else {
                    alert('Failed to delete client.');
                }
            } catch (error) {
                alert('An error occurred while deleting the client.');
            }
        }
    }
}
    
    function renderPetDetailPage(patientId) {
        const patient = allPatients.find(p => p.id == patientId);
        if (!patient) { navigateToPage('records'); return; }

        document.getElementById('pet-detail-icon').className = `bx ${patient.species === 'Canine' ? 'bxs-dog' : (patient.species === 'Feline' ? 'bxs-cat' : 'bxs-bug')}`;
        document.getElementById('pet-detail-name').textContent = patient.petName;
        document.getElementById('pet-detail-owner').textContent = `Owner: ${patient.ownerName}`;
        document.getElementById('pet-detail-species').textContent = patient.species;
        document.getElementById('pet-detail-breed').textContent = patient.breed || 'N/A';
        document.getElementById('pet-detail-sex').textContent = patient.sex || 'N/A';
        document.getElementById('pet-detail-dob').textContent = patient.dob ? new Date(patient.dob).toLocaleDateString() : 'N/A';

        const visitHistoryContainer = document.getElementById('pet-visit-history-container');
        visitHistoryContainer.innerHTML = '';
        const patientVisits = allVisits.filter(v => v.patientId == patientId).sort((a, b) => new Date(b.date) - new Date(a.date));
        
        if (!patientVisits.length) { 
            visitHistoryContainer.innerHTML = `<p class="placeholder-text">No visit history found.</p>`; 
            return; 
        }

        patientVisits.forEach(v => {
            const formattedDate = new Date(v.date).toLocaleString();
            visitHistoryContainer.innerHTML += `
                <div class="visit-card">
                    <div class="visit-card-header">
                        <span>${formattedDate} - ${v.service}</span>
                        <button class="join-now secondary-btn edit-record-btn" data-appointment-id="${v.id}">View/Edit Record</button>
                    </div>
                    <div class="visit-card-body">
                        <h4>Subjective</h4><p>${v.subjective || 'No notes.'}</p>
                        <h4>Objective</h4><p>${v.objective || 'No notes.'}</p>
                        <h4>Assessment</h4><p>${v.assessment || 'No notes.'}</p>
                        <h4>Plan</h4><p>${v.plan || 'No notes.'}</p>
                    </div>
                </div>`;
        });
    }

    init();
});