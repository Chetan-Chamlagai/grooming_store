<?php
// admin/views/user_address.php - Complete Customer Addresses Directory View Module

$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT a.id, a.address_line, a.city, a.province, a.postal_code, a.is_default, u.full_name, u.email 
        FROM user_addresses a 
        JOIN users u ON a.user_id = u.id";

switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY u.full_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY u.full_name DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY a.id ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY a.id DESC";
        break;
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <h1 class="header-title">Customer Addresses Directory</h1>
    
    <div class="section-box">
        <!-- Controls Toolbar (Live Search + Dropdown Sorting) -->
        <div class="controls-toolbar">
            <div class="search-wrap">
                <input type="text" id="liveSearchInput" placeholder="Type to instantly filter by customer name, city, province, or postal code..." autocomplete="off">
            </div>
            <div class="sort-wrap">
                <form method="GET" action="admin_dashboard.php" id="sortForm" style="margin: 0;">
                    <input type="hidden" name="view" value="user_address">
                    <select name="sort" onchange="document.getElementById('sortForm').submit()">
                        <option value="newest" <?php if($sort=='newest') echo 'selected'; ?>>Sort: Newest Added</option>
                        <option value="oldest" <?php if($sort=='oldest') echo 'selected'; ?>>Sort: Oldest Added</option>
                        <option value="name_asc" <?php if($sort=='name_asc') echo 'selected'; ?>>Sort: Customer Name (A-Z)</option>
                        <option value="name_desc" <?php if($sort=='name_desc') echo 'selected'; ?>>Sort: Customer Name (Z-A)</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Addresses Data Table -->
        <table id="addressTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Address Line</th>
                    <th>City / Province</th>
                    <th>Postal Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($addr = $result->fetch_assoc()): ?>
                    <tr class="address-row">
                        <td>#<?php echo $addr['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($addr['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($addr['address_line']); ?></td>
                        <td><?php echo htmlspecialchars($addr['city'] . (!empty($addr['province']) ? ', ' . $addr['province'] : '')); ?></td>
                        <td><?php echo htmlspecialchars($addr['postal_code']); ?></td>
                        <td>
                            <?php if ($addr['is_default'] == 1): ?>
                                <span style="color: var(--brand-gold); font-weight: 500; font-size: 8px; letter-spacing: 0.2em; text-transform: uppercase;">Default</span>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 9px;">Secondary</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">No saved addresses found in the system.</td>
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
    let rows = document.querySelectorAll('#addressTable tbody tr.address-row');

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