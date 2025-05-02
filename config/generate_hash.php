<?php
// Change 'test1234' to the password you wish to test.
$plainText = 'test1234';
$hashedPassword = password_hash($plainText, PASSWORD_DEFAULT);
echo $hashedPassword;
?>

