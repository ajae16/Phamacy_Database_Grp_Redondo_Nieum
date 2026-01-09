// Role Management UI logic

document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if roles section exists (for Account Management integration)
    if (document.getElementById('rolesTableBody')) {
        loadRoles();
        window.editRole = editRole;
    }
    if (document.getElementById('accountUsersTableBody')) {
        loadAccountUsers();
        window.showAssignRoleModal = showAssignRoleModal;
        window.closeAssignRoleModal = closeAssignRoleModal;
        window.submitAssignRole = submitAssignRole;
    }
});

// Load roles for Account Management roles table
async function loadRoles() {
    const res = await fetch('api/roles.php');
    const result = await res.json();
    const tbody = document.getElementById('rolesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (result.success && result.data) {
        result.data.forEach(role => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${role.displayName || role.roleName}</td><td>${role.description || ''}</td><td><button class="btn-edit" onclick="editRole(${role.roleId})">Edit</button></td>`;
            tbody.appendChild(row);
        });
        const roleCount = document.getElementById('roleCount');
        if (roleCount) roleCount.textContent = `${result.data.length} roles`;
    }
    // Store roles for assign modal
    window._allRoles = result.data || [];
}

// Load users for Account Management (if needed)
async function loadAccountUsers() {
    const res = await fetch('api/accounts.php');
    const result = await res.json();
    const tbody = document.getElementById('accountUsersTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (result.success && result.data) {
        result.data.forEach(user => {
            const row = document.createElement('tr');
            const userId = user.accountId || user.id || '';
            const username = user.username || '';
            const fullName = user.fullName || `${user.firstName || ''} ${user.middleName || ''} ${user.lastName || ''}`.replace(/\s+/g, ' ').trim();
            const email = user.email || '';
            let roles = Array.isArray(user.roles) ? user.roles.map(r => `<span class='role-badge'>${r}</span>`).join(' ') : (user.roles || '');
            if (!roles) roles = '<span class="role-badge">Employee</span>';
            const status = user.status || '';
            const createdAt = user.createdAt || '';
            row.innerHTML = `
                <td>${userId}</td>
                <td>${username}</td>
                <td>${fullName}</td>
                <td>${email}</td>
                <td>${roles}</td>
                <td>${status}</td>
                <td>${createdAt}</td>
                <td class="action-buttons">
                    <button class="btn-edit" onclick="editUser('${userId}')">Edit</button>
                    <button class="btn-delete" onclick="deleteUser('${userId}')">Delete</button>
                    <button class="btn-primary" onclick="showAssignRoleModal('${userId}')">Assign Role</button>
                </td>
            `;
            tbody.appendChild(row);
        });
        // Add style for role badges if not already present
        if (!document.getElementById('role-badge-style')) {
            const style = document.createElement('style');
            style.id = 'role-badge-style';
            style.innerHTML = `.role-badge { display: inline-block; background: #64748b; color: #fff; border-radius: 8px; padding: 2px 8px; font-size: 11px; margin-right: 4px; margin-bottom: 2px; }`;
            document.head.appendChild(style);
        }
    }
}
async function loadUsers() {
    const res = await fetch('api/accounts.php');
    const result = await res.json();
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    if (result.success && result.data) {
        result.data.forEach(user => {
            const row = document.createElement('tr');
            // Show full name properly
            const fullName = user.fullName || `${user.firstName || ''} ${user.middleName || ''} ${user.lastName || ''}`.replace(/\s+/g, ' ').trim();
            let roles = Array.isArray(user.roles) ? user.roles.map(r => `<span class='role-badge'>${r}</span>`).join(' ') : (user.roles || '');
            if (!roles) roles = '<span class="role-badge">Employee</span>';
            row.innerHTML = `<td>${fullName}</td><td>${roles}</td><td>${user.status || ''}</td>
                <td><button onclick="showAssignRoleModal('${user.accountId}')">Assign Role</button></td>`;
            tbody.appendChild(row);
        });
        // Add style for role badges
        const style = document.createElement('style');
        style.innerHTML = `.role-badge { display: inline-block; background: #64748b; color: #fff; border-radius: 8px; padding: 2px 8px; font-size: 11px; margin-right: 4px; margin-bottom: 2px; }`;
        document.head.appendChild(style);
    }
}

function showRoleModal() {
    document.getElementById('roleModalTitle').textContent = 'Add Role';
    document.getElementById('roleForm').reset();
    document.getElementById('roleModal').style.display = 'block';
}

function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
}

async function submitRole() {
    const roleName = document.getElementById('roleName').value.trim();
    const displayName = document.getElementById('displayName').value.trim();
    const description = document.getElementById('description').value.trim();
    if (!roleName) { alert('Role name required'); return; }
    const res = await fetch('api/roles.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({roleName, displayName, description})
    });
    const result = await res.json();
    if (result.success) {
        alert('Role added');
        closeRoleModal();
        loadRoles();
    } else {
        alert('Error: ' + result.message);
    }
}

function showAssignRoleModal(userId) {
    // Show modal to pick a role
    const modal = document.getElementById('assignRoleModal');
    const userIdInput = document.getElementById('assignUserId');
    const select = document.getElementById('assignRoleSelect');
    if (!modal || !userIdInput || !select) return;
    userIdInput.value = userId;
    select.innerHTML = '';
    (window._allRoles || []).forEach(role => {
        const opt = document.createElement('option');
        opt.value = role.roleId;
        opt.textContent = role.displayName || role.roleName;
        select.appendChild(opt);
    });
    modal.style.display = 'block';
}

function closeAssignRoleModal() {
    document.getElementById('assignRoleModal').style.display = 'none';
}

async function submitAssignRole() {
    const userId = document.getElementById('assignUserId').value;
    const roleId = document.getElementById('assignRoleSelect').value;
    await assignRole(userId, roleId);
    closeAssignRoleModal();
}

// Add editRole function
function editRole(roleId) {
    const role = (window._allRoles || []).find(r => r.roleId == roleId);
    if (!role) return alert('Role not found');
    const modalTitle = document.getElementById('roleModalTitle');
    const roleNameInput = document.getElementById('roleName');
    const displayNameInput = document.getElementById('displayName');
    const descriptionInput = document.getElementById('description');
    const modal = document.getElementById('roleModal');
    if (!modalTitle || !roleNameInput || !displayNameInput || !descriptionInput || !modal) return;
    modalTitle.textContent = 'Edit Role';
    roleNameInput.value = role.roleName;
    displayNameInput.value = role.displayName || '';
    descriptionInput.value = role.description || '';
    modal.style.display = 'block';
    // Optionally, store editing roleId for update
    window._editingRoleId = roleId;
}

async function assignRole(userId, roleId) {
    const res = await fetch('api/roles.php?action=assign', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({userId, roleId}),
        credentials: 'include'
    });
    const result = await res.json();
    if (result.success) {
        alert('Role assigned');
        loadUsers();
    } else {
        alert('Error: ' + result.message);
    }
}
