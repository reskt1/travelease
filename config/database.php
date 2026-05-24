<?php
try{
    $pdo =new PDO("mysql:host=localhost;dbname=travelease;charset=utf8",
    "root",
    "",
    [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
    );
}catch(PDOException $e){
    die("koneksi gagal:".$e->getMessage());
}
?>