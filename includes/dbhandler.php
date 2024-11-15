<?php 

function getConnection(){
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "adressverwaltung";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function close_connection ($conn){
    try {
        if (isset($conn)) {
            $conn->close();
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        throw $e;
    }
}

function safePerson($salutation, $firstname, $lastname, $email, $mobile_number, $phone_number, $homepage, $street, $house_number, $postal_code, $city){
    
    try{
    $conn = getConnection();
    $sql = "INSERT INTO persons(salutation, firstname, lastname, email, mobile_number, phone_number, homepage) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $salutation, $firstname, $lastname, $email, $mobile_number, $phone_number, $homepage);
    $stmt->execute();

    $person_id = $conn->insert_id;

    $sql = "INSERT INTO addresses(person_id, street, house_number, postal_code, city) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $person_id, $street, $house_number, $postal_code, $city);
    $stmt->execute();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        throw $e;
    } finally {
        close_connection($conn);
        header("Location: ../index.php");
        exit();
    }
}

function getPersons(){
    $conn = getConnection();
    $sql = "SELECT * FROM persons INNER JOIN addresses ON persons.id = addresses.person_id";
    $result = $conn->query($sql);
    $persons = array();
    if ($result->num_rows > 0){
        while ($row = $result->fetch_assoc()){
            $persons[] = $row;
        }
    }
    close_connection($conn);
    return $persons;
}

function getSpecificPerson($person_id) {
    $conn = getConnection();
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM persons INNER JOIN addresses ON persons.id = addresses.person_id WHERE persons.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "No records found for person_id: " . htmlspecialchars($person_id);
        return null;
    }

    $person = $result->fetch_assoc();
    if (!$person) {
        echo "Fetch failed.";
        return null;
    }

    close_connection($conn);
    return $person;
}

function updatePerson($person_id, $salutation, $firstname, $lastname, $email, $mobile_number, $phone_number, $homepage, $street, $house_number, $postal_code, $city) {
    try {
        $conn = getConnection();
        
        $sql = "UPDATE persons SET salutation = ?, firstname = ?, lastname = ?, email = ?, mobile_number = ?, phone_number = ?, homepage = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $salutation, $firstname, $lastname, $email, $mobile_number, $phone_number, $homepage, $person_id);
        $stmt->execute();
        
        $sql = "UPDATE addresses SET street = ?, house_number = ?, postal_code = ?, city = ? WHERE person_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $street, $house_number, $postal_code, $city, $person_id);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        throw $e;
    } finally {
        close_connection($conn);
    }
}