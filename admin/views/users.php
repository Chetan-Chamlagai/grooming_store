<?php
// admin/views/users.php - Complete Customer Directory View Module

$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT id, full_name, email, phone_number, created_at FROM users WHERE role = 'customer'";

switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY full_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY full_name DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <h1 class="header-title">Customer Users Directory</h1>
    
    <div class="section-box">
        <!-- Controls Toolbar (Live Search + Dropdown Sorting) -->
        <div class="controls-toolbar">
            <div class="search-wrap">
                <input type="text" id="liveSearchInput" placeholder="Type to instantly filter by name, email, or phone..." autocomplete="off">
            </div>
            <div class="sort-wrap">
                <form method="GET" action="admin_dashboard.php" id="sortForm" style="margin: 0;">
                    <input type="hidden" name="view" value="users">
                    <select name="sort" onchange="document.getElementById('sortForm').submit()">
                        <option value="newest" <?php if($sort=='newest') echo 'selected'; ?>>Sort: Newest Registered</option>
                        <option value="oldest" <?php if($sort=='oldest') echo 'selected'; ?>>Sort: Oldest Registered</option>
                        <option value="name_asc" <?php if($sort=='name_asc') echo 'selected'; ?>>Sort: Alphabetical (A-Z)</option>
                        <option value="name_desc" <?php if($sort=='name_desc') echo 'selected'; ?>>Sort: Alphabetical (Z-A)</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Customers Data Table -->
        <table id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>Date of Creation</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($cust = $result->fetch_assoc()): ?>
                    <tr class="customer-row">
                        <td>#<?php echo $cust['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($cust['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cust['email']); ?></td>
                        <td><?php echo htmlspecialchars($cust['phone_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($cust['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 40px;">No registered customers found in the system.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Instant Client-Side Typing Search Script -->
<script>
document.getElementById('liveSearchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#customerTable tbody tr.customer-row');

    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>