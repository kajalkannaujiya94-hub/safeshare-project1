<?php

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generate_jwt($payload, $secret = "safeshare_secret", $exp = 3600) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['exp'] = time() + $exp;

    $base64Header = base64url_encode(json_encode($header));
    $base64Payload = base64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true);
    $base64Signature = base64url_encode($signature);

    return "$base64Header.$base64Payload.$base64Signature";
}

function verify_jwt($jwt, $secret = "safeshare_secret") {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    [$header, $payload, $signature] = $parts;
    $check = base64url_encode(hash_hmac('sha256', "$header.$payload", $secret, true));

    if ($check !== $signature) return false;

    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    if ($data['exp'] < time()) return false;

    return $data;
}
?>