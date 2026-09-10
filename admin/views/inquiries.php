<?php
// admin/views/inquiries.php - Customer Inquiries & Bespoke Consultations View

$feedback = ['type' => '', 'text' => ''];
$subject_filter = $_GET['subject'] ?? 'all';

// Handle Status Updates (e.g., mark as read / responded)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $inquiry_id = intval($_POST['inquiry_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? 'read');

    if ($inquiry_id > 0 && in_array($new_status, ['unread', 'read', 'responded'])) {
        $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $inquiry_id);
        if ($stmt->execute()) {
            $feedback = ['type' => 'success', 'text' => "Inquiry #{$inquiry_id} status updated to " . strtoupper($new_status) . "."];
        } else {
            $feedback = ['type' => 'error', 'text' => "Failed to update inquiry status."];
        }
        $stmt->close();
    }
}

// Build Query with Optional Subject Filter
$sql = "SELECT id, first_name, last_name, email, subject, message, status, created_at FROM inquiries WHERE 1=1";
$params = [];
$types = "";

if ($subject_filter !== 'all' && !empty($subject_filter)) {
    $sql .= " AND subject = ?";
    $params[] = $subject_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inquiries_result = $stmt->get_result();
?>

<div style="display: flex; flex-direction: column; gap: 25px;">
    <div>
        <h1 class="header-title">Client Inquiries &amp; Consultations</h1>
        <p style="font-size: 10px; letter-spacing: 0.25em; text-transform: uppercase; color: var(--text-secondary); margin-top: 6px;">
            Client Communications &bull; General Inquiries, Bespoke Consultations &amp; Order Support
        </p>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($feedback['text'])): ?>
        <div style="padding: 14px 20px; font-size: 11px; border: 1px solid <?php echo $feedback['type'] === 'success' ? '#B2D8B2' : '#E0B4B4'; ?>; background: <?php echo $feedback['type'] === 'success' ? '#EAF4EA' : '#FDF2F2'; ?>; color: <?php echo $feedback['type'] === 'success' ? '#2C5E2C' : '#912D2D'; ?>;">
            <?php echo htmlspecialchars($feedback['text']); ?>
        </div>
    <?php endif; ?>

    <div class="section-box">
        <!-- Controls Toolbar: Live Search & Subject Filter -->
        <div class="controls-toolbar">
            <div class="search-wrap">
                <input type="text" id="liveInquirySearch" placeholder="Type to instantly filter by client name, email, or message..." autocomplete="off">
            </div>

            <div class="sort-wrap">
                <form method="GET" action="admin_dashboard.php" id="inquiryFilterForm" style="margin: 0;">
                    <input type="hidden" name="view" value="inquiries">
                    <select name="subject" onchange="document.getElementById('inquiryFilterForm').submit()">
                        <option value="all" <?php if($subject_filter == 'all') echo 'selected'; ?>>Filter: All Subjects</option>
                        <option value="General Inquiry" <?php if($subject_filter == 'General Inquiry') echo 'selected'; ?>>General Inquiry</option>
                        <option value="Bespoke Consultation" <?php if($subject_filter == 'Bespoke Consultation') echo 'selected'; ?>>Bespoke Consultation</option>
                        <option value="Order Status" <?php if($subject_filter == 'Order Status') echo 'selected'; ?>>Order Status</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Inquiries Table -->
        <table id="inquiryTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Email Address</th>
                    <th>Subject</th>
                    <th style="width: 35%;">Message</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($inquiries_result && $inquiries_result->num_rows > 0): ?>
                    <?php while($row = $inquiries_result->fetch_assoc()): ?>
                    <?php 
                        $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                        $is_unread = ($row['status'] === 'unread');
                    ?>
                    <tr class="inquiry-row" style="<?php echo $is_unread ? 'background: rgba(197, 160, 89, 0.05);' : ''; ?>">
                        <td>#<?php echo $row['id']; ?></td>
                        <td><strong class="search-client"><?php echo $full_name; ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="search-email" style="color: var(--text-primary); text-decoration: underline;">
                                <?php echo htmlspecialchars($row['email']); ?>
                            </a>
                        </td>
                        <td>
                            <span style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--brand-gold); font-weight: 500;">
                                <?php echo htmlspecialchars($row['subject']); ?>
                            </span>
                        </td>
                        <td class="search-message" style="font-size: 11px; line-height: 1.5; color: var(--text-primary);">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </td>
                        <td style="font-size: 10px; color: var(--text-secondary); white-space: nowrap;">
                            <?php echo date('Y/m/d', strtotime($row['created_at'])); ?>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 3px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; <?php 
                                echo $row['status'] === 'unread' ? 'background: #9B2C2C; color: #fff;' : 
                                    ($row['status'] === 'responded' ? 'background: #2C5E2C; color: #fff;' : 'background: #E5E5E0; color: #555;'); 
                            ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="admin_dashboard.php?view=inquiries" style="margin: 0; display: flex; gap: 4px;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="inquiry_id" value="<?php echo $row['id']; ?>">
                                
                                <?php if ($row['status'] !== 'responded'): ?>
                                    <button type="submit" name="status" value="responded" class="btn" style="padding: 4px 8px; font-size: 7px; background: var(--brand-gold);">Responded</button>
                                <?php endif; ?>

                                <?php if ($row['status'] === 'unread'): ?>
                                    <button type="submit" name="status" value="read" class="btn" style="padding: 4px 8px; font-size: 7px; background: transparent; color: var(--text-primary); border: 1px solid var(--borders);">Read</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 40px;">No client inquiries found matching your filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Real-time Typing Search Script -->
<script>
document.getElementById('liveInquirySearch')?.addEventListener('keyup', function() {
    let query = this.value.toLowerCase();
    let rows = document.querySelectorAll('#inquiryTable tbody tr.inquiry-row');

    rows.forEach(row => {
        let content = row.textContent.toLowerCase();
        if (content.includes(query)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>