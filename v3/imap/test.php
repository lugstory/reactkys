<?php

include("chilkat.php");

// The Chilkat API can be unlocked for a fully-functional 30-day trial by passing any
// string to the UnlockBundle method.  A program can unlock once at the start. Once unlocked,
// all subsequently instantiated objects are created in the unlocked state. 
// 
// After licensing Chilkat, replace the "Anything for 30-day trial" with the purchased unlock code.
// To verify the purchased unlock code was recognized, examine the contents of the LastErrorText
// property after unlocking.  For example:
$glob = new CkGlobal();
$success = $glob->UnlockBundle('Anything for 30-day trial');
if ($success != true) {
    print $glob->lastErrorText() . "\n";
    exit;
}

$status = $glob->get_UnlockStatus();
if ($status == 2) {
    print 'Unlocked using purchased unlock code.' . "\n";
}
else {
    print 'Unlocked in trial mode.' . "\n";
}

$crypt = new CkCrypt2();

//  AES is also known as Rijndael.
$crypt->CryptAlgorithm = 'aes';

//  CipherMode may be "ecb" or "cbc"
$crypt->CipherMode = 'cbc';

//  KeyLength may be 128, 192, 256
$crypt->KeyLength = 256;

//  The padding scheme determines the contents of the bytes
//  that are added to pad the result to a multiple of the
//  encryption algorithm's block size.  AES has a block
//  size of 16 bytes, so encrypted output is always
//  a multiple of 16.
$crypt->PaddingScheme = 0;

//  EncodingMode specifies the encoding of the output for
//  encryption, and the input for decryption.
//  It may be "hex", "url", "base64", or "quoted-printable".
$crypt->EncodingMode = 'hex';

//  An initialization vector is required if using CBC mode.
//  ECB mode does not use an IV.
//  The length of the IV is equal to the algorithm's block size.
//  It is NOT equal to the length of the key.
$ivHex = '000102030405060708090A0B0C0D0E0F';
$crypt->SetEncodedIV($ivHex,'hex');

//  The secret key must equal the size of the key.  For
//  256-bit encryption, the binary secret key is 32 bytes.
//  For 128-bit encryption, the binary secret key is 16 bytes.
$keyHex = '000102030405060708090A0B0C0D0E0F101112131415161718191A1B1C1D1E1F';
$crypt->SetEncodedKey($keyHex,'hex');

//  Encrypt a string...
//  The input string is 44 ANSI characters (i.e. 44 bytes), so
//  the output should be 48 bytes (a multiple of 16).
//  Because the output is a hex string, it should
//  be 96 characters long (2 chars per byte).
$encStr = $crypt->encryptStringENC('The quick brown fox jumps over the lazy dog.');
print $encStr . "\n";

//  Now decrypt:
$decStr = $crypt->decryptStringENC($encStr);
print $decStr . "\n";

?>

