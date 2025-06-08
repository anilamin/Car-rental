<?php
$conn = new mysqli("localhost", "anil", "anil.amin8779", "car_rental");
if ($conn->connect_error) {
    die("خطا: " . $conn->connect_error);
}
echo "اتصال موفق!";
$conn->close();
?>
<?php
$conn = new mysqli("localhost", "anil", "anil.amin8779", "car_rental");
if ($conn->connect_error) {
    die("خطا: " . $conn->connect_error);
} 
echo "اتصال موفق!";
$conn->close();
?>