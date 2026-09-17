<?php 

// hii statement itacheck kama user ana session yoyote kwenye system, kama hana itastart session mpya
if(session_status() == PHP_SESSION_NONE){
    session_start(); 
}

// hii functiion itaangalia kama user yuko logged in, kama yuko logged in itarudisha the user id
function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

//hii function itarudisha user id ya user aliye login, kama hana session itarudisha null
function currentUserId(){
    return $_SESSION['user_id'] ?? null;
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id']; // hapa tunaweka user id kwenye session alionayo
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'university_id' => $user['univeristy_id']
    ]; // hapa tunaweka data za user kwenye session yake
}

function login_user(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        // FIX: Fixed spelling matching the database row
        'university_id' => $user['university_id'] ?? null
    ];
}
