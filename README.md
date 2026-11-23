# Backup codes for Moodle multi-factor authentication (factor_backupcodes)

Ten one-time codes users can use to sign in when they do not have their authenticator app or security key: a lost phone, a new phone, a dead battery on exam day. Without them, every lost phone is a ticket for the site administrator to reset the user's MFA.

## Set it up

1. Site administration > Plugins > Admin tools > Multi-factor authentication > Manage multi-factor authentication: enable **Backup codes** (keep the weight at 100 so a code is enough to sign in, like the other factors).
2. Users open **Preferences > Multi-factor authentication preferences > Set up backup codes**, save the ten codes (download as a text file, a password manager or a printout), tick "I have saved these codes somewhere safe", and save.

At sign in, the MFA page offers **Use a backup code**. Each code works once. Users can make a new set at any time, which stops the old codes working. When the last code is used, they are told to make a new set.

## Good to know

- Only one-way hashes of the codes are stored (PHP password_hash), so a database copy does not reveal them.
- Codes look like `k7m3q-x9wdp`: no i, l, o, 0 or 1. Capitals, spaces and the hyphen are ignored when typing.
- Wrong codes count towards MFA's lockout, like other factors.
- The factor's label shows how many codes are left, for example "Backup codes (9 left)".

Moodle 4.5 to 5.3. Built by DevLMS: https://devlms.com
