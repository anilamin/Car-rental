<?php
$preselected_car = isset($_GET['car']) ? urldecode($_GET['car']) : '';
$preselected_price = isset($_GET['price']) ? (float)$_GET['price'] : '';
$preselected_img = isset($_GET['img']) ? $_GET['img'] : '';


if (isset($_GET['car']) && isset($_GET['price'])) {
    $preselected_car = htmlspecialchars($_GET['car']);
    $preselected_price = (float)$_GET['price'];
}
ini_set('display_errors', 1);
error_reporting(E_ALL);
// تنظیمات اتصال به دیتابیس (فقط یکبار)
$servername = "localhost";
$username = "root"; // استفاده از کاربر پیشفرض
$password = ""; // رمز پیشفرض خالی
$dbname = "car_rental";

// ایجاد اتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// بررسی خطا
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_name = $_POST['car_name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $total = $price*$quantity;

    $sql = "INSERT INTO reservations (car_name, price, quantity, total)
            VALUES ('$car_name', '$price', '$quantity', '$total')";

    if ($conn->query($sql) === TRUE) {
        $success_message = "Reservation saved successfully!";
    } else {
        $error_message = "Error: " . $sql . "<br>" . $conn->error;
    }
}
// Handle delete request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['car_name'])) {
    $car_name_to_delete = $conn->real_escape_string($_GET['car_name']);
    $delete_sql = "DELETE FROM reservations WHERE car_name = '$car_name_to_delete'";
    
    if ($conn->query($delete_sql) === TRUE) {
        $success_message = "Reservation deleted successfully!";
        // Clear the form after deletion
        $preselected_car = '';
        $preselected_price = '';
        $preselected_img = '';
    } else {
        $error_message = "Error deleting reservation: " . $conn->error;
    }
}
// Handle update request
if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['car_name']) && isset($_GET['quantity'])) {
    $car_name_to_update = $conn->real_escape_string($_GET['car_name']);
    $new_quantity = (int)$_GET['quantity'];
    
    // Get current price to calculate new total
    $get_price_sql = "SELECT price FROM reservations WHERE car_name = '$car_name_to_update'";
    $result = $conn->query($get_price_sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $price = $row['price'];
        $new_total = $price * $new_quantity;
        
        $update_sql = "UPDATE reservations SET 
                      quantity = '$new_quantity', 
                      total = '$new_total' 
                      WHERE car_name = '$car_name_to_update'";
        
        if ($conn->query($update_sql) === TRUE) {
            $success_message = "Reservation updated successfully!";
            // Update the displayed values
            $preselected_price = $price;
            $_GET['price'] = $price; // To keep the price in URL if needed
        } else {
            $error_message = "Error updating reservation: " . $conn->error;
        }
    } else {
        $error_message = "Reservation not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Reservation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color:rgb(229, 217, 212);
        margin: 0;
        padding: 20px;
        color: #333;
    }
    
    .edit-btn {
    background-color:rgb(132, 125, 119);
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.edit-btn:hover {
    background-color:rgb(103, 87, 82);
}


    .reservation-container {
        display: flex;
        max-width: 1200px;
        margin: 0 auto;
        gap: 20px;
        align-items: flex-start;
    }
    
    /* استایل مشترک برای هر دو باکس */
    .reservation-box, .car-image-box {
        background-color: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 25px;
        min-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        margin-top: 100px;
        transition: all 0.3s ease;
         height: fit-content; /* ارتفاع متناسب با محتوا */
        flex: 1;
    }
    
    .reservation-box:hover, .car-image-box:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
    
    /* استایل هدر */
    .reservation-header, .car-image-header {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 25px;
        font-size: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f1f1;
    }
    
    /* استایل آیتم‌های فرم */
    .reservation-item {
        margin-bottom: 20px;
    }
    
    .reservation-item label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #34495e;
    }
    
    .reservation-item input, 
    .reservation-item select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 15px;
        transition: border 0.3s;
    }
    
    .reservation-item input:focus, 
    .reservation-item select:focus {
        border-color:rgb(148, 128, 128);
        outline: none;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    /* استایل دکمه‌ها */
    .buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
    }
    
    .delete-btn {
        background-color:rgb(219, 97, 4);
        color: white;
        border: none;
        padding: 12px 18px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .delete-btn:hover {
        background-color:rgb(198, 87, 1);
    }
    
    .reserve-btn {
        background-color:rgb(219, 97, 4);
        color: white;
        border: none;
        padding: 12px 18px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .reserve-btn:hover {
        background-color: rgb(198, 87, 1);
    }
    
    /* استایل بخش نمایش مجموع */
    .total-sum {
        font-weight: bold;
        font-size: 18px;
        margin-top: 15px;
        text-align: center;
        padding: 12px;
        background-color: #f8f9fa;
        border-radius: 8px;
        color: #2c3e50;
        border: 1px dashed #ddd;
    }
    
    /* استایل نمایش خودرو */
    .car-display {
        margin-top: 20px;
        padding: 15px;
        border: 1px dashed #ddd;
        border-radius: 8px;
        min-height: 50px;
        background-color: #f8f9fa;
        text-align: center;
        font-size: 16px;
    }
    
    /* استایل بخش نمایش عکس ماشین */
    .car-image-display {
        margin-top: 20px;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        background-color: #f8f9fa;
        border: 1px dashed #ddd;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .car-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* استایل جزئیات ماشین */
    .car-details {
        margin-top: 20px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #ddd;
    }
    
    .car-detail-item {
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .car-detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .car-detail-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    /* استایل پیام‌ها */
    .message {
        padding: 12px;
        margin: 15px 0;
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
    }
    
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    /* استایل فوتر */
    footer {
        text-align: center;
        padding: 30px 20px;
        margin-top: 50px;
    }
    
    footer button {
        padding: 12px 24px;
        font-size: 16px;
        background-color: #2c3e50;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        font-weight: 600;
    }
    
    footer button:hover {
        background-color: #1a252f;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* استایل برای حالت موبایل */
    @media (max-width: 768px) {
        .reservation-container {
            flex-direction: column;
            align-items: center;
        }
        
        .reservation-box, .car-image-box {
            width: 90%;
            margin-top: 30px;
        }
    }
</style>
</head>
<body>
   
    <div class="reservation-container">
        <div class="reservation-box">
            <div class="reservation-header">
                <i class="fas fa-car"></i>
                Car Reservation
            </div>
            <?php if (isset($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="reservation-item">
                    <label for="car_name">
                        <i class="fas fa-car-side"></i>
                        Car Name
                    </label>
                    <input type="text" id="car_name" name="car_name" 
           value="<?php echo htmlspecialchars($preselected_car); ?>" 
           placeholder="Enter car name" required readonly>
                </div>
                
                <div class="reservation-item">
                    <label for="price">
                        <i class="fas fa-tag"></i>
                        Price 
                    </label>
                    <input type="number" id="price" name="price" 
           value="<?php echo htmlspecialchars($preselected_price); ?>" 
           placeholder="Enter price" required readonly>
           
                </div>
                
                <div class="reservation-item">
                    <label for="quantity">
                        <i class="fas fa-list-ol"></i>
                        Quantity
                    </label>
                    <select id="quantity" name="quantity" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                </div>
                
                <div class="car-display" id="car-display">
                    <?php if (isset($car_name)): ?>
                        Selected Car: <?php echo htmlspecialchars($car_name); ?>
                    <?php else: ?>
                        No car selected
                    <?php endif; ?>
                </div>
                
                <div class="total-sum" id="total-sum">
                    Total: $0
                </div>
                
                <div class="buttons">
    <a href="?action=delete&car_name=<?php echo urlencode($preselected_car); ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this reservation?')">
        <i class="fas fa-trash"></i>
        Delete
    </a>
    <button type="button" class="edit-btn" onclick="updateReservationInDB()">
        <i class="fas fa-edit"></i>
        Edit
    </button>
    <button type="submit" class="reserve-btn" onclick="this.disabled=true; this.form.submit();">
        <i class="fas fa-calendar-check"></i>
        Reserve
    </button>
</div>
            </form>
        </div>
            <div class="car-image-box">
            <div class="car-image-header">
                <i class="fas fa-image"></i>
                Car Image & Details
            </div>
            
            <div class="car-image-display">
                <?php if (!empty($preselected_img)): ?>
                    <img src="<?php echo htmlspecialchars($preselected_img); ?>" alt="Car Image" class="car-image">
                <?php else: ?>
                    <div style="padding: 90px; text-align: center; color: #777;">
                        <i class="fas fa-car" style="font-size: 50px;"></i>
                        <p>No car image selected</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="car-details">
                <div class="car-detail-item">
                    <span class="car-detail-label">Car Model:</span>
                    <span id="detail-model"><?php echo htmlspecialchars($preselected_car); ?></span>
                </div>
                <div class="car-detail-item">
                    <span class="car-detail-label">Daily Price:</span>
                    <span id="detail-price">$<?php echo htmlspecialchars($preselected_price); ?></span>
                </div>
                <div class="car-detail-item">
                    <span class="car-detail-label">Availability:</span>
                    <span style="color: green;">In Stock</span>
                </div>
                <div class="car-detail-item">
                    <span class="car-detail-label">Rating:</span>
                    <span>
                        <i class="fas fa-star" style="color: gold;"></i>
                        <i class="fas fa-star" style="color: gold;"></i>
                        <i class="fas fa-star" style="color: gold;"></i>
                        <i class="fas fa-star" style="color: gold;"></i>
                        <i class="fas fa-star-half-alt" style="color: gold;"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateReservationInDB() {
    const carName = document.getElementById('car_name').value;
    const quantity = document.getElementById('quantity').value;
    
    if (!carName) {
        alert('Please select a car first');
        return;
    }
    
    if (confirm('Are you sure you want to update the quantity?')) {
        window.location.href = `?action=update&car_name=${encodeURIComponent(carName)}&quantity=${quantity}`;
    }
}
        // Update total and display car name
        document.getElementById('quantity').addEventListener('change', updateReservation);
        document.getElementById('price').addEventListener('input', updateReservation);
        document.getElementById('car_name').addEventListener('input', updateReservation);

        function updateReservation() {
            const carName = document.getElementById('car_name').value;
            const price = parseInt(document.getElementById('price').value) || 0;
            const quantity = parseInt(document.getElementById('quantity').value);
            const total = price * quantity;
            
            // Update car display
            document.getElementById('car-display').textContent = carName 
                ? `Selected Car: ${carName}` 
                : "No car selected";
            
            // Update total
            document.getElementById('total-sum').textContent = `Total: $${total}`;
        }

        function clearForm() {
            document.getElementById('car_name').value = '';
            document.getElementById('price').value = '';
            document.getElementById('quantity').value = '1';
            document.getElementById('car-display').textContent = 'No car selected';
            document.getElementById('total-sum').textContent = 'Total: $0';
             // پاک کردن فرم عکس و جزئیات
        document.getElementById('detail-model').textContent = 'N/A';
        document.getElementById('detail-price').textContent = '$0';
        
        // اگر تصویری نمایش داده می‌شود، آن را پاک کنید
        const carImageDisplay = document.querySelector('.car-image-display');
        carImageDisplay.innerHTML = `
            <div style="padding: 50px; text-align: center; color: #777;">
                <i class="fas fa-car" style="font-size: 50px;"></i>
                <p>No car image selected</p>
            </div>
        `;
        // حذف پارامترهای URL بدون ریلود صفحه
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
        }

          }
    </script>

    <footer style="text-align: center; padding: 20px; margin-top: 50px;">
        
  <button onclick="history.back()" style="
    padding: 10px 20px;
    font-size: 16px;
    background-color:rgb(85, 79, 79);
    color:rgb(255, 255, 255);
    border: none;
    border-radius: 8px;
    cursor: pointer;
     box-shadow: 0 5px 5px rgb(255, 255, 255);
  ">
   back to home
  </button>
</footer>
</body>
</html>
<?php $conn->close(); ?>