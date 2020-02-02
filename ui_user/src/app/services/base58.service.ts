/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import {Api} from "./api.service";
import {SettingsService} from "./settings/settings.service";
import {HttpClient} from "@angular/common/http";

declare const $: any;
declare const jsSHA: any;

@Injectable()
export class Base58Service {
    constructor(
        public api: Api,
        public settings: SettingsService,
        private http: HttpClient
    ) {
    }


    /* Convert a hex char to value */
    hexChar2byte(c) {
        let d = 0;
        if (c >= 'A' && c <= 'F') {
            d = c.charCodeAt(0) - 'A'.charCodeAt(0) + 10;
        }
        else if (c >= 'a' && c <= 'f') {
            d = c.charCodeAt(0) - 'a'.charCodeAt(0) + 10;
        }
        else if (c >= '0' && c <= '9') {
            d = c.charCodeAt(0) - '0'.charCodeAt(0);
        }
        return d;
    }

    /* Check if a char is hex char */
    isHexChar(c) {
        if ((c >= 'A' && c <= 'F') ||
            (c >= 'a' && c <= 'f') ||
            (c >= '0' && c <= '9')) {
            return 1;
        }
        return 0;
    }

    /* Convert HEX string to byte array */

    hexStr2byteArray(str) {
        let byteArray = Array();
        let d = 0;
        let i = 0;
        let j = 0;
        let k = 0;

        for (i = 0; i < str.length; i++) {
            let c = str.charAt(i);
            if (this.isHexChar(c)) {
                d <<= 4;
                d += this.hexChar2byte(c);
                j++;
                if (0 == (j % 2)) {
                    byteArray[k++] = d;
                    d = 0;
                }
            }
        }
        return byteArray;
    }

    /* Convert a byte to string */
    byte2hexStr(byte) {
        let hexByteMap = "0123456789ABCDEF";
        let str = "";
        str += hexByteMap.charAt(byte >> 4);
        str += hexByteMap.charAt(byte & 0x0f);
        return str;
    }

    /* Convert byte arry to HEX string */

    byteArray2hexStr(byteArray) {
        let str = "";
        let i;
        for (i = 0; i < (byteArray.length - 1); i++) {
            str += this.byte2hexStr(byteArray[i]);
        }
        str += this.byte2hexStr(byteArray[i]);
        return str;
    }

    SHA256(msgBytes) {
        let shaObj = new jsSHA("SHA-256", "HEX");
        let msgHex = this.byteArray2hexStr(msgBytes);
        shaObj.update(msgHex);
        let hashHex = shaObj.getHash("HEX");
        let hashBytes = this.hexStr2byteArray(hashHex);
        return hashBytes;
    }

    encode58(buffer) {
        if (buffer.length === 0) {
            return ''
        }

        const BASE = 58;
        const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

        let i, j, digits = [0];
        for (i = 0; i < buffer.length; i++) {
            for (j = 0; j < digits.length; j++) {
                digits[j] <<= 8
            }

            digits[0] += buffer[i];

            let carry = 0;
            for (j = 0; j < digits.length; ++j) {
                digits[j] += carry

                carry = (digits[j] / BASE) | 0;
                digits[j] %= BASE;
            }

            while (carry) {
                digits.push(carry % BASE);

                carry = (carry / BASE) | 0;
            }
        }

        // deal with leading zeros
        for (i = 0; buffer[i] === 0 && i < buffer.length - 1; i++) {
            digits.push(0);
        }

        return digits.reverse().map(function (digit) {
            return ALPHABET[digit]
        }).join('')
    }

    getBase58CheckAddress(addressBytes) {
        let hash0 = this.SHA256(addressBytes);
        let hash1 = this.SHA256(hash0);
        let checkSum = hash1.slice(0, 4);
        checkSum = addressBytes.concat(checkSum);
        let base58Check = this.encode58(checkSum);

        return base58Check;
    }

    encode(src) {
        if (src.length < 2 || (src.length & 1) != 0) {
            alert("Input length error!");
            return;
        }
        let bytes = this.hexStr2byteArray(src);
        return this.getBase58CheckAddress(bytes);
    }

    decode58(string) {
        if (string.length === 0) {
            return [];
        }

        const BASE = 58;
        const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        let ALPHABET_MAP = {}
        for (let i = 0; i < ALPHABET.length; i++) {
            ALPHABET_MAP[ALPHABET.charAt(i)] = i;
        }

        let i, j, bytes = [0];
        for (i = 0; i < string.length; i++) {
            let c = string[i];
            if (!(c in ALPHABET_MAP)) {
                throw new Error('Non-base58 character');
            }

            for (j = 0; j < bytes.length; j++) {
                bytes[j] *= BASE;
            }
            bytes[0] += ALPHABET_MAP[c];

            let carry = 0;
            for (j = 0; j < bytes.length; ++j) {
                bytes[j] += carry;

                carry = bytes[j] >> 8;
                bytes[j] &= 0xff;
            }

            while (carry) {
                bytes.push(carry & 0xff);

                carry >>= 8;
            }
        }

        // deal with leading zeros
        for (i = 0; string[i] === '1' && i < string.length - 1; i++) {
            bytes.push(0);
        }

        return bytes.reverse()
    }
    
    decodeBase58Address(base58Sting) {
        let zeroAddress = this.hexStr2byteArray("000000000000000000000000000000000000000000");
        if (typeof (base58Sting) != 'string') {
            return false;
        }
        if (base58Sting.length <= 4) {
            return false;
        }
        let address = this.decode58(base58Sting);
        if (base58Sting.length <= 4) {
            return false;
        }
        let len = address.length;
        let offset = len - 4;
        let checkSum = address.slice(offset);
        address = address.slice(0, offset);
        let hash0 = this.SHA256(address);
        let hash1 = this.SHA256(hash0);
        let checkSum1 = hash1.slice(0, 4);
        if (checkSum[0] == checkSum1[0] && checkSum[1] == checkSum1[1] && checkSum[2]
            == checkSum1[2] && checkSum[3] == checkSum1[3]
        ) {
            return address;
        }
        return false;
    }
    
    decode(src) {
        let bytes = this.decodeBase58Address(src);
        if (bytes === false) {
            return false;
        }
        return this.byteArray2hexStr(bytes);
    }
}

