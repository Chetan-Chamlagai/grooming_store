<?php
// index.php - Oggentleme Unified Client Storefront
session_start();

// Database Connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'grooming_store';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Map database categories to UI filter tags
$category_map = [
    'fragrances' => 'fragrance',
    'watches'    => 'timepiece',
    'wallets'    => 'leather'
];

// Fetch active products
$sql = "SELECT id, title, description, price, category, photo, stock_quantity FROM products WHERE status = 'active' ORDER BY id DESC";
$products_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OGGENTLEME — Fine Masculine Luxury</title>
    <link rel="shortcut icon" href="images/favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Montserrat:wght@200;300;400;500&display=swap" rel="stylesheet">
    
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
        (function() {
            emailjs.init("tbh4MzQaE0W5pVfQT");
        })();

        const EMAILJS_SERVICE_ID = 'service_037qe38';
        const EMAILJS_TEMPLATE_ID = 'template_5o44yuh';

        function calculateCartTotals() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const tax = subtotal * 0.13;
            const shipping = subtotal > 0 ? 150 : 0;
            const grandTotal = subtotal + tax + shipping;

            return { cart, subtotal, tax, shipping, grandTotal };
        }

        function openCheckout() {
            renderCartModal();
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        function renderCartModal() {
            const { cart, subtotal, tax, shipping, grandTotal } = calculateCartTotals();
            const itemsList = document.getElementById('cartItemsList');

            if (cart.length === 0) {
                itemsList.innerHTML = `<p style="font-size: 11px; color: var(--text-secondary); padding: 20px 0;">Your bag is currently empty.</p>`;
            } else {
                itemsList.innerHTML = cart.map((item, index) => `
                    <div class="cart-item-row">
                        <div>
                            <strong>${item.name}</strong>
                            <div style="color: var(--text-secondary); font-size: 9px;">Qty: ${item.quantity} &bull; Rs. ${item.price.toLocaleString()} each</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span>Rs. ${(item.price * item.quantity).toLocaleString()}</span>
                            <button onclick="removeFromBag(${index})" style="background:none; border:none; color: #992222; font-size: 10px; cursor:pointer;">&times;</button>
                        </div>
                    </div>
                `).join('');
            }

            document.getElementById('billSubtotal').textContent = `Rs. ${subtotal.toLocaleString()}`;
            document.getElementById('billTax').textContent = `Rs. ${tax.toLocaleString()}`;
            document.getElementById('billShipping').textContent = `Rs. ${shipping.toLocaleString()}`;
            document.getElementById('billGrandTotal').textContent = `Rs. ${grandTotal.toLocaleString()}`;
        }

        function removeFromBag(index) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartBadge();
            renderCartModal();
        }

        async function processCheckout(event) {
            event.preventDefault();
            const { cart, subtotal, tax, shipping, grandTotal } = calculateCartTotals();

            if (cart.length === 0) {
                alert("Your bag is currently empty.");
                return;
            }

            const orderSummary = cart.map(item => 
                `- ${item.name} (ID: ${item.id}) | Qty: ${item.quantity} | Unit: Rs. ${item.price.toLocaleString()} | Total: Rs. ${(item.price * item.quantity).toLocaleString()}`
            ).join("\n");

            const orderId = "OG-" + Math.floor(100000 + Math.random() * 900000);

            const templateParams = {
                order_id: orderId,
                customer_name: document.getElementById('custName').value,
                customer_email: document.getElementById('custEmail').value,
                customer_phone: document.getElementById('custPhone').value,
                customer_address: document.getElementById('custAddress').value,
                order_summary: orderSummary,
                subtotal: `Rs. ${subtotal.toLocaleString()}`,
                tax: `Rs. ${tax.toLocaleString()}`,
                shipping: `Rs. ${shipping.toLocaleString()}`,
                grand_total: `Rs. ${grandTotal.toLocaleString()}`
            };

            try {
                const response = await emailjs.send(
                    EMAILJS_SERVICE_ID, 
                    EMAILJS_TEMPLATE_ID, 
                    templateParams
                );

                if (response.status === 200) {
                    const existingOrders = JSON.parse(localStorage.getItem('orders')) || [];
                    existingOrders.push({
                        ...templateParams,
                        items: cart,
                        status: 'Pending',
                        date: new Date().toLocaleDateString()
                    });
                    localStorage.setItem('orders', JSON.stringify(existingOrders));

                    localStorage.setItem('cart', JSON.stringify([]));
                    updateCartBadge();
                    closeCheckout();

                    alert(`Order Placed Successfully!\nOrder ID: ${orderId}\nA confirmation email has been dispatched.`);
                    event.target.reset();
                }
            } catch (error) {
                console.error('Checkout Error:', error);
                alert('Failed to submit order. Please check your connection and try again.');
            }
        }

        function addToBag(productId, productName, price) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const existingIndex = cart.findIndex(item => item.id === productId);

            if (existingIndex > -1) {
                cart[existingIndex].quantity += 1;
            } else {
                cart.push({ id: productId, name: productName, price: price, quantity: 1 });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartBadge();
            alert(`"${productName}" has been added to your shopping bag.`);
        }

        function updateCartBadge() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = totalCount;
        }

        document.addEventListener('DOMContentLoaded', updateCartBadge);
    </script>

    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-main: #F8F6F1;
            --text-primary: #111111;
            --text-secondary: #555555;
            --brand-gold: #B89455;
            --dark-section: #111111;
            --gold-on-dark: #B89455;
            --cards: #FFFFFF;
            --borders: #D8C39A;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 60px;
            background: rgba(248, 246, 241, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--borders);
        }

        .nav-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 400;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-logo img {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .nav-logo span { color: var(--brand-gold); }

        .nav-links {
            display: flex;
            gap: 40px;
            list-style: none;
        }

        .nav-links a {
            font-size: 10px;
            font-weight: 300;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active { color: var(--brand-gold); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-auth-link {
            font-size: 9px;
            font-weight: 300;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-auth-link:hover { color: var(--brand-gold); }

        .nav-cart-btn {
            background: none;
            border: none;
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
        }

        .cart-badge {
            background-color: var(--brand-gold);
            color: #FFFFFF;
            font-size: 8px;
            padding: 2px 6px;
            border-radius: 50%;
        }

        header.hero {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding-top: 90px;
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px 60px;
        }

        .hero-eyebrow {
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            color: var(--brand-gold);
            margin-bottom: 24px;
        }

        .hero-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 64px;
            font-weight: 300;
            line-height: 1.1;
            color: var(--text-primary);
            margin-bottom: 24px;
        }

        .hero-title em {
            font-style: italic;
            color: var(--text-secondary);
        }

        .hero-desc {
            font-size: 12px;
            font-weight: 300;
            line-height: 2.2;
            color: var(--text-secondary);
            max-width: 400px;
            margin-bottom: 40px;
            letter-spacing: 0.05em;
        }

        .hero-cta-group {
            display: flex;
            gap: 20px;
        }

        .btn-primary {
            padding: 16px 40px;
            background: var(--text-primary);
            color: var(--bg-main);
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid var(--text-primary);
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background: var(--brand-gold);
            border-color: var(--brand-gold);
            color: #FFFFFF;
        }

        .btn-outline {
            padding: 16px 40px;
            background: transparent;
            color: var(--text-primary);
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid var(--borders);
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s, color 0.3s;
        }

        .btn-outline:hover {
            border-color: var(--brand-gold);
            color: var(--brand-gold);
        }

        .hero-visual {
            background-color: var(--dark-section);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-monogram-box {
            width: 320px;
            height: 440px;
            border: 1px solid var(--borders);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.02);
        }

        .hero-monogram-box h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            color: var(--gold-on-dark);
            font-weight: 300;
            letter-spacing: 0.2em;
            margin-bottom: 12px;
        }

        .hero-monogram-box p {
            font-size: 8px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--borders);
            border-bottom: 1px solid var(--borders);
            background: var(--cards);
        }

        .stat-item {
            padding: 36px 20px;
            text-align: center;
            border-right: 1px solid var(--borders);
        }

        .stat-item:last-child { border-right: none; }

        .stat-number {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 300;
            color: var(--text-primary);
            display: block;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 8px;
            font-weight: 400;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        section.catalog { padding: 100px 60px; }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-eyebrow {
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            color: var(--brand-gold);
            margin-bottom: 16px;
            display: block;
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            font-weight: 300;
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 11px;
            font-weight: 300;
            color: var(--text-secondary);
            max-width: 460px;
            margin: 0 auto;
            letter-spacing: 0.05em;
        }

        .filter-bar {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 50px;
        }

        .filter-btn {
            padding: 10px 24px;
            font-size: 9px;
            font-weight: 300;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            background: transparent;
            border: 1px solid var(--borders);
            color: var(--text-secondary);
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--text-primary);
            color: #FFFFFF;
            border-color: var(--text-primary);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .product-card {
            background: var(--cards);
            border: 1px solid var(--borders);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-img-area {
            width: 100%;
            aspect-ratio: 4/5;
            background: var(--bg-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--borders);
            position: relative;
            overflow: hidden;
        }

        .product-img-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-silhouette {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            color: var(--borders);
            letter-spacing: 0.15em;
        }

        .product-info {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }

        .product-category {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .product-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 400;
            color: var(--text-primary);
            letter-spacing: 0.03em;
        }

        .product-notes {
            font-size: 10px;
            color: var(--text-secondary);
            font-weight: 300;
            margin-bottom: 12px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid rgba(216, 195, 154, 0.3);
        }

        .product-price {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 500;
            color: var(--brand-gold);
        }

        .btn-add {
            background: transparent;
            border: 1px solid var(--text-primary);
            color: var(--text-primary);
            padding: 8px 16px;
            font-size: 8px;
            font-weight: 400;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: background 0.3s, color 0.3s, border-color 0.3s;
        }

        .btn-add:hover {
            background: var(--brand-gold);
            border-color: var(--brand-gold);
            color: #FFFFFF;
        }

        section.dark-showcase {
            background-color: var(--dark-section);
            color: #FFFFFF;
            padding: 120px 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .dark-showcase-content h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            font-weight: 300;
            line-height: 1.2;
            margin-bottom: 24px;
            color: #FFFFFF;
        }

        .dark-showcase-content h2 span {
            color: var(--gold-on-dark);
            font-style: italic;
        }

        .dark-showcase-content p {
            font-size: 11px;
            font-weight: 300;
            color: #A09A8E;
            line-height: 2.2;
            margin-bottom: 40px;
            letter-spacing: 0.05em;
        }

        .dark-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .dark-feature-item h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            color: var(--gold-on-dark);
            margin-bottom: 8px;
            font-weight: 400;
        }

        .dark-feature-item p {
            font-size: 10px;
            color: #8A857B;
            margin-bottom: 0;
            line-height: 1.8;
        }

        .dark-showcase-visual {
            border: 1px solid var(--borders);
            height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.01);
        }

        .dark-monogram-emblem { text-align: center; }

        .dark-monogram-emblem span {
            font-family: 'Cormorant Garamond', serif;
            font-size: 72px;
            color: var(--gold-on-dark);
            font-weight: 300;
            display: block;
            letter-spacing: 0.1em;
            margin-bottom: 10px;
        }

        .dark-monogram-emblem small {
            font-size: 8px;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            color: #8A857B;
        }

        section.inquiry {
            padding: 100px 60px;
            background-color: var(--bg-main);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .inquiry-info h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            font-weight: 300;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .inquiry-info p {
            font-size: 11px;
            font-weight: 300;
            color: var(--text-secondary);
            line-height: 2.1;
            margin-bottom: 36px;
            letter-spacing: 0.04em;
        }

        .inquiry-detail { margin-bottom: 20px; }

        .inquiry-detail-label {
            font-size: 8px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--brand-gold);
            display: block;
            margin-bottom: 4px;
        }

        .inquiry-detail-val {
            font-size: 12px;
            color: var(--text-primary);
            font-weight: 300;
            letter-spacing: 0.05em;
        }

        .inquiry-form {
            background: var(--cards);
            border: 1px solid var(--borders);
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 8px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .form-group input, .form-group textarea, .form-group select {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            padding: 12px 16px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: var(--brand-gold);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(17, 17, 17, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-container {
            background: var(--bg-main);
            border: 1px solid var(--borders);
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            padding: 40px;
        }

        .modal-close {
            position: absolute;
            top: 20px; right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--text-primary);
            cursor: pointer;
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .cart-items-list {
            max-height: 220px;
            overflow-y: auto;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--borders);
        }

        .cart-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid rgba(216, 195, 154, 0.3);
            font-size: 11px;
        }

        .bill-breakdown {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: var(--cards);
            padding: 16px;
            border: 1px solid var(--borders);
        }

        .bill-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--text-secondary);
        }

        .bill-row.total-row {
            font-size: 12px;
            font-weight: 500;
            color: var(--brand-gold);
            border-top: 1px solid var(--borders);
            padding-top: 8px;
            margin-top: 4px;
        }

        footer {
            background-color: var(--dark-section);
            color: #FFFFFF;
            padding: 50px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid rgba(216, 195, 154, 0.15);
        }

        .footer-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #FFFFFF;
        }

        .footer-logo span { color: var(--gold-on-dark); }

        .footer-copy {
            font-size: 9px;
            font-weight: 300;
            letter-spacing: 0.2em;
            color: #8A857B;
        }

        .footer-links {
            display: flex;
            gap: 30px;
        }

        .footer-links a {
            font-size: 9px;
            font-weight: 300;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #8A857B;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover { color: var(--gold-on-dark); }

        @media (max-width: 1024px) {
            nav { padding: 20px 30px; }
            header.hero { grid-template-columns: 1fr; min-height: auto; }
            .hero-content { padding: 100px 30px 60px; }
            .hero-visual { min-height: 350px; }
            .product-grid { grid-template-columns: repeat(2, 1fr); }
            section.dark-showcase { grid-template-columns: 1fr; padding: 80px 30px; }
            section.inquiry { grid-template-columns: 1fr; padding: 80px 30px; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .stat-item:nth-child(2) { border-right: none; }
            .stat-item:nth-child(3) { border-top: 1px solid var(--borders); }
            .stat-item:nth-child(4) { border-top: 1px solid var(--borders); border-right: none; }
            .product-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .modal-body { grid-template-columns: 1fr; }
            footer { flex-direction: column; gap: 20px; text-align: center; padding: 40px 20px; }
        }
    </style>
</head>
<body>

    <!-- NAVIGATION -->
    <nav>
        <a class="nav-logo" href="#">
            <img src="images/logo.png" alt="Oggentleme" onerror="this.style.display='none'">
            OGGENTLEME<span>.</span>
        </a>
        <ul class="nav-links">
            <li><a href="#home" class="active">Home</a></li>
            <li><a href="#catalog">Collection</a></li>
            <li><a href="#about">Philosophy</a></li>
            <li><a href="#inquiry">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="account.php" class="nav-auth-link">Account</a>
                <a href="logout.php" class="nav-auth-link" onclick="return confirm('Are you sure you want to sign out of your account?');">Sign Out</a>            <?php else: ?>
                <a href="login.php" class="nav-auth-link">Sign In</a>
            <?php endif; ?>
            
            <button class="nav-cart-btn" onclick="openCheckout()">
                Bag <span class="cart-badge">0</span>
            </button>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <header class="hero" id="home">
        <div class="hero-content">
            <span class="hero-eyebrow">Fine Masculine Luxury &bull; Est. 2026</span>
            <h1 class="hero-title">The Art of<br><em>Quiet</em><br>Distinction</h1>
            <p class="hero-desc">Crafted for the modern gentleman who speaks little and commands everything. A curated assembly of rare fragrances, tailored timepieces, and supple leatherwork.</p>
            <div class="hero-cta-group">
                <a href="#catalog" class="btn-primary">Explore Collection</a>
                <a href="#inquiry" class="btn-outline">Inquire Bespoke</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-monogram-box">
                <h2>OG</h2>
                <p>Signature Monogram</p>
            </div>
        </div>
    </header>

    <!-- STATS BAR -->
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-number">09</span>
            <span class="stat-label">Signature Scents</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">12</span>
            <span class="stat-label">Rare Ingredients</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">48h</span>
            <span class="stat-label">Lasting Depth</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">100%</span>
            <span class="stat-label">Handcrafted Quality</span>
        </div>
    </div>

    <!-- CATALOG SECTION (POPULATED VIA DATABASE) -->
    <section class="catalog" id="catalog">
        <div class="section-header">
            <span class="section-eyebrow">The Masterpieces</span>
            <h2 class="section-title">Curated Essentials</h2>
            <p class="section-desc">Each artifact is developed with architectural precision and uncompromising masculine elegance.</p>
        </div>

        <div class="filter-bar">
            <button class="filter-btn active" onclick="filterCatalog('all', this)">All Items</button>
            <button class="filter-btn" onclick="filterCatalog('fragrance', this)">Fragrances</button>
            <button class="filter-btn" onclick="filterCatalog('timepiece', this)">Timepieces</button>
            <button class="filter-btn" onclick="filterCatalog('leather', this)">Leather Goods</button>
        </div>

        <div class="product-grid" id="productGrid">
            <?php if ($products_result && $products_result->num_rows > 0): ?>
                <?php while($prod = $products_result->fetch_assoc()): ?>
                    <?php 
                        $ui_cat = $category_map[$prod['category']] ?? 'general';
                    ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($ui_cat); ?>">
                        <div class="product-img-area">
                            <?php if (!empty($prod['photo']) && file_exists($prod['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($prod['photo']); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>">
                            <?php else: ?>
                                <span class="product-silhouette">OG &bull; <?php echo sprintf("%02d", $prod['id']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars($prod['category']); ?> &bull; In Stock (<?php echo intval($prod['stock_quantity']); ?>)</span>
                            <h3 class="product-name"><?php echo htmlspecialchars($prod['title']); ?></h3>
                            <p class="product-notes"><?php echo htmlspecialchars($prod['description']); ?></p>
                            <div class="product-footer-row">
                                <span class="product-price">Rs. <?php echo number_format($prod['price']); ?></span>
                                <button class="btn-add" onclick="addToBag(<?php echo $prod['id']; ?>, '<?php echo htmlspecialchars(addslashes($prod['title'])); ?>', <?php echo (float)$prod['price']; ?>)">Add to Bag</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--text-secondary); padding: 50px;">No active products currently available in the collection.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- DARK SHOWCASE SECTION -->
    <section class="dark-showcase" id="about">
        <div class="dark-showcase-content">
            <span class="section-eyebrow">Architectural Heritage</span>
            <h2>Designed for those who <span>command</span> the room in silence.</h2>
            <p>At Oggentleme, we reject excess. Every bottle, casing, and strap is subjected to rigorous material selection processes. Our formulations draw from rare botanical extractions, yielding an enduring trail of sophistication.</p>
            <div class="dark-features">
                <div class="dark-feature-item">
                    <h4>01. Raw Ingredients</h4>
                    <p>Sourced from sustainable private reserves across the globe.</p>
                </div>
                <div class="dark-feature-item">
                    <h4>02. Structural Balance</h4>
                    <p>Proportioned to feel weighted and anchored in the hand.</p>
                </div>
            </div>
        </div>
        <div class="dark-showcase-visual">
            <div class="dark-monogram-emblem">
                <span>OG</span>
                <small>Excellence in Restraint</small>
            </div>
        </div>
    </section>

  <!-- INQUIRY SECTION -->
    <section class="inquiry" id="inquiry">
        <div class="inquiry-info">
            <span class="section-eyebrow">Personal Concierge</span>
            <h2>Get in Touch</h2>
            <p>Whether you require assistance with a bespoke fragrance selection, custom leather engraving, or order inquiries, our concierge is at your service.</p>
            
            <div class="inquiry-detail">
                <span class="inquiry-detail-label">Direct Line</span>
                <span class="inquiry-detail-val">oggentlemeinfo@gmail.com</span>
            </div>
            <div class="inquiry-detail" style="margin-top: 16px;">
                <span class="inquiry-detail-label">Location</span>
                <span class="inquiry-detail-val">Damak, Jhapa, Nepal</span>
            </div>
        </div>
        <form class="inquiry-form" method="POST" action="submit_inquiry.php">
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="Rohan" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Sharma">
            </div>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label>Subject / Interest</label>
            <select name="subject">
                <option value="General Inquiry">General Inquiry</option>
                <option value="Bespoke Consultation">Bespoke Consultation</option>
                <option value="Order Status">Order Status</option>
            </select>
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="message" placeholder="Write your message here..." required></textarea>
        </div>
        <button type="submit" class="btn-primary" style="width: 100%;">Send Inquiry</button>
    </form>
    </section>

    <!-- CHECKOUT MODAL -->
    <div id="checkoutModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <button class="modal-close" onclick="closeCheckout()">&times;</button>
            
            <div class="modal-body">
                <!-- Left: Order Summary -->
                <div class="order-summary-side">
                    <span class="section-eyebrow">Your Bag</span>
                    <h3 class="section-title" style="font-size: 28px;">Order Summary</h3>
                    
                    <div id="cartItemsList" class="cart-items-list"></div>

                    <div class="bill-breakdown">
                        <div class="bill-row">
                            <span>Subtotal</span>
                            <span id="billSubtotal">Rs. 0</span>
                        </div>
                        <div class="bill-row">
                            <span>Tax (13% VAT)</span>
                            <span id="billTax">Rs. 0</span>
                        </div>
                        <div class="bill-row">
                            <span>Shipping</span>
                            <span id="billShipping">Rs. 0</span>
                        </div>
                        <div class="bill-row total-row">
                            <span>Grand Total</span>
                            <span id="billGrandTotal">Rs. 0</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Customer Shipping Form -->
                <div class="checkout-form-side">
                    <span class="section-eyebrow">Delivery Details</span>
                    <h3 class="section-title" style="font-size: 28px;">Shipping Address</h3>
                    
                    <form id="checkoutForm" onsubmit="processCheckout(event)">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="custName" placeholder="Rohan Sharma" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" id="custEmail" placeholder="you@example.com" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" id="custPhone" placeholder="98XXXXXXXX" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Shipping Address</label>
                            <textarea id="custAddress" placeholder="Street, City, District" required></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Place Order via Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-logo">OGGENTLEME<span>.</span></div>
        <div class="footer-copy">&copy; 2026 Oggentleme. All rights reserved.</div>
        <div class="footer-links">
            <a href="#">Instagram</a>
            <a href="#">Facebook</a>
            <a href="#">WhatsApp</a>
        </div>
    </footer>

    <!-- CATALOG FILTER & FORM SCRIPTS -->
    <script>
        function filterCatalog(category, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            alert('Your message has been received. Our concierge will be in touch shortly.');
            e.target.reset();
        }
    </script>
</body>
</html>