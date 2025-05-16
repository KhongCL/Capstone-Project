/**
 * User management functions for TrafAnalyz
 */

// Main user management object
const UserManagement = {
    
    /**
     * Suspend a user account
     * @param {number} userId - User ID to suspend
     * @param {function} callback - Callback function after operation completes
     */
    suspendUser: function(userId, callback) {
        this.performUserAction('suspend', userId, callback);
    },
    
    /**
     * Restore a suspended user account
     * @param {number} userId - User ID to restore
     * @param {function} callback - Callback function after operation completes
     */
    restoreUser: function(userId, callback) {
        this.performUserAction('restore', userId, callback);
    },
    
    /**
     * Delete a user account
     * @param {number} userId - User ID to delete
     * @param {function} callback - Callback function after operation completes
     */
    deleteUser: function(userId, callback) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            this.performUserAction('delete', userId, callback);
        }
    },
    
    /**
     * Get all users
     * @param {function} callback - Callback function with users data
     */
    getAllUsers: function(callback) {
        this.performUserAction('get_users', null, callback);
    },
    
    /**
     * Perform a user action through the API
     * @param {string} action - Action to perform (suspend, restore, delete, get_users)
     * @param {number|null} userId - User ID for the action
     * @param {function} callback - Callback function after operation completes
     */
    performUserAction: function(action, userId, callback) {
        // Build request data
        const data = {
            action: action
        };
        
        if (userId !== null) {
            data.user_id = userId;
        }
        
        // Send AJAX request to API
        fetch('api_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error performing user action:', error);
            if (callback && typeof callback === 'function') {
                callback({
                    success: false,
                    message: 'Network error occurred'
                });
            }
        });
    },
    
    /**
     * Initialize user management interface
     * @param {string} containerId - ID of container element for user management
     */
    initUserManagement: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Load users and create interface
        this.getAllUsers(response => {
            if (response.success && response.data) {
                this.renderUserTable(container, response.data);
            } else {
                container.innerHTML = `<div class="error">Error loading users: ${response.message}</div>`;
            }
        });
    },
    
    /**
     * Render user table in specified container
     * @param {Element} container - Container element
     * @param {Array} users - Array of user objects
     */
    renderUserTable: function(container, users) {
        if (!users || users.length === 0) {
            container.innerHTML = '<p>No users found.</p>';
            return;
        }
        
        // Create table HTML
        let tableHtml = `
        <table class="data-table user-management-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>`;
        
        users.forEach(user => {
            tableHtml += `
            <tr data-user-id="${user.UserID}">
                <td>${user.UserID}</td>
                <td>${user.Username}</td>
                <td>${user.FullName || 'Not provided'}</td>
                <td>${user.Email}</td>
                <td>${user.Role}</td>
                <td>
                    <span class="status-badge status-${user.AccountStatus.toLowerCase()}">${user.AccountStatus}</span>
                </td>
                <td class="actions">
                    ${user.AccountStatus === 'Active' ? 
                        `<button class="btn-small btn-suspend" data-user-id="${user.UserID}">Suspend</button>` : 
                        `<button class="btn-small btn-restore" data-user-id="${user.UserID}">Restore</button>`
                    }
                    <button class="btn-small btn-delete" data-user-id="${user.UserID}">Delete</button>
                </td>
            </tr>`;
        });
        
        tableHtml += `
            </tbody>
        </table>`;
        
        // Add table to container
        container.innerHTML = tableHtml;
        
        // Add event listeners to action buttons
        const self = this;
        container.querySelectorAll('.btn-suspend').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                self.suspendUser(userId, response => {
                    if (response.success) {
                        self.initUserManagement(container.id); // Reload table
                    } else {
                        alert('Failed to suspend user: ' + response.message);
                    }
                });
            });
        });
        
        container.querySelectorAll('.btn-restore').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                self.restoreUser(userId, response => {
                    if (response.success) {
                        self.initUserManagement(container.id); // Reload table
                    } else {
                        alert('Failed to restore user: ' + response.message);
                    }
                });
            });
        });
        
        container.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                self.deleteUser(userId, response => {
                    if (response.success) {
                        self.initUserManagement(container.id); // Reload table
                    } else {
                        alert('Failed to delete user: ' + response.message);
                    }
                });
            });
        });
    }
};