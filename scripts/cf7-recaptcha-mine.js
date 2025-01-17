// Full implementation of SHA265 hashing algorithm.
function sha256( ascii ) {
    function rightRotate( value, amount ) {
        return ( value>>>amount ) | ( value<<(32 - amount ) );
    }

    var mathPow = Math.pow;
    var maxWord = mathPow( 2, 32 );
    var lengthProperty = 'length';

    // Used as a counter across the whole file
    var i, j;
    var result = '';

    var words = [];
    var asciiBitLength = ascii[ lengthProperty ] * 8;

    // Caching results is optional - remove/add slash from front of this line to toggle.
    // Initial hash value: first 32 bits of the fractional parts of the square roots of the first 8 primes
    // (we actually calculate the first 64, but extra values are just ignored).
    var hash = sha256.h = sha256.h || [];

    // Round constants: First 32 bits of the fractional parts of the cube roots of the first 64 primes.
    var k = sha256.k = sha256.k || [];
    var primeCounter = k[ lengthProperty ];

    var isComposite = {};
    for ( var candidate = 2; primeCounter < 64; candidate++ ) {
        if ( ! isComposite[ candidate ] ) {
            for ( i = 0; i < 313; i += candidate ) {
                isComposite[ i ] = candidate;
            }
            hash[ primeCounter ] = ( mathPow( candidate, 0.5 ) * maxWord ) | 0;
            k[ primeCounter++ ] = ( mathPow( candidate, 1 / 3 ) * maxWord ) | 0;
        }
    }

    // Append Ƈ' bit (plus zero padding).
    ascii += '\x80';

    // More zero padding
    while ( ascii[ lengthProperty ] % 64 - 56 ){
      ascii += '\x00';
    }

    for ( i = 0, max = ascii[ lengthProperty ]; i < max; i++ ) {
        j = ascii.charCodeAt( i );

        // ASCII check: only accept characters in range 0-255
        if ( j >> 8 ) {
          return;
        }
        words[ i >> 2 ] |= j << ( ( 3 - i ) % 4 ) * 8;
    }
    words[ words[ lengthProperty ] ] = ( ( asciiBitLength / maxWord ) | 0 );
    words[ words[ lengthProperty ] ] = ( asciiBitLength );

    // process each chunk
    for ( j = 0, max = words[ lengthProperty ]; j < max; ) {

        // The message is expanded into 64 words as part of the iteration
        var w = words.slice( j, j += 16 );
        var oldHash = hash;

        // This is now the undefinedworking hash, often labelled as variables a...g
        // (we have to truncate as well, otherwise extra entries at the end accumulate.
        hash = hash.slice( 0, 8 );

        for ( i = 0; i < 64; i++ ) {
            var i2 = i + j;

            // Expand the message into 64 words
            var w15 = w[ i - 15 ], w2 = w[ i - 2 ];

            // Iterate
            var a = hash[ 0 ], e = hash[ 4 ];
            var temp1 = hash[ 7 ]
                + ( rightRotate( e, 6 ) ^ rightRotate( e, 11 ) ^ rightRotate( e, 25 ) ) // S1
                + ( ( e&hash[ 5 ] ) ^ ( ( ~e ) &hash[ 6 ] ) ) // ch
                + k[i]
                // Expand the message schedule if needed
                + ( w[ i ] = ( i < 16 ) ? w[ i ] : (
                        w[ i - 16 ]
                        + ( rightRotate( w15, 7 ) ^ rightRotate( w15, 18 ) ^ ( w15 >>> 3 ) ) // s0
                        + w[ i - 7 ]
                        + ( rightRotate( w2, 17 ) ^ rightRotate( w2, 19 ) ^ ( w2 >>> 10 ) ) // s1
                    ) | 0
                  );

            // This is only used once, so *could* be moved below, but it only saves 4 bytes and makes things unreadble:
            var temp2 = ( rightRotate( a, 2 ) ^ rightRotate( a, 13 ) ^ rightRotate( a, 22 ) ) // S0
                + ( ( a&hash[ 1 ] )^( a&hash[ 2 ] )^( hash[ 1 ]&hash[ 2 ] ) ); // maj

                // We don't bother trimming off the extra ones,
                // they're harmless as long as we're truncating when we do the slice().
            hash = [ ( temp1 + temp2 )|0 ].concat( hash );
            hash[ 4 ] = ( hash[ 4 ] + temp1 ) | 0;
        }

        for ( i = 0; i < 8; i++ ) {
            hash[ i ] = ( hash[ i ] + oldHash[ i ] ) | 0;
        }
    }

    for ( i = 0; i < 8; i++ ) {
        for ( j = 3; j + 1; j-- ) {
            var b = ( hash[ i ]>>( j * 8 ) ) & 255;
            result += ( ( b < 16 ) ? 0 : '' ) + b.toString( 16 );
        }
    }
    return result;
}

// Replace with your desired hash function.
function hashFunc( x ) {
    return sha256( x );
}

function setFormData( x, y ) {
  var z = document.getElementById( x );
  if( z ) {
    z.value = y;
  }
}

function getFormData( x ) {
  var z = document.getElementById( x );
  if( z ){
    return z.value;
  }else{
    return '';
  }
}

// Convert hex char to binary string.
function hexInBin( x ) {
  var ret = '';
  switch( x.toUpperCase() ) {
    case '0':
      return '0000';
      break;
    case '1':
      return '0001';
      break;
    case '2':
      return '0010';
      break;
    case '3':
      return '0011';
      break;
    case '4':
      return '0100';
      break;
    case '5':
      return '0101';
      break;
    case '6':
      return '0110';
      break;
    case '7':
      return '0111';
      break;
    case '8':
      return '1000';
      break;
    case '9':
      return '1001';
      break;
    case 'A':
      return '1010';
      break;
    case 'B':
      return '1011';
      break;
    case 'C':
      return '1100';
      break;
    case 'D':
      return '1101';
      break;
    case 'E':
      return '1110';
      break;
    case 'F':
      return '1111';
      break;
    default :
      return '0000';
  }
}

// Gets the leading number of bits from the string.
function extractBits( hexString, numBits ) {
  var bitString = '';
  var numChars = Math.ceil( numBits / 4 );
  for ( var i = 0; i < numChars; i++ ){
    bitString = bitString + '' + hexInBin( hexString.charAt( i ) );
  }

  bitString = bitString.substr( 0, numBits );
  return bitString;
}

// Check if a given nonce is a solution for this stamp and difficulty
// the $difficulty number of leading bits must all be 0 to have a valid solution.
function checkNonce( difficulty, stamp, nonce ) {
  var colHash = hashFunc( stamp + nonce );
  var checkBits = extractBits( colHash, difficulty );
  return ( checkBits == 0 );
}

function sleep( ms ) {
  return new Promise( resolve => setTimeout( resolve, ms ) );
}

// Iterate through as many nonces as it takes to find one that gives us a solution hash at the target difficulty.
async function findHash() {
  document.querySelector('.rcm-loading').style.display = 'flex';
  var hashStamp = getFormData( 'hashStamp' );
  var hashDifficulty = getFormData( 'hashDifficulty' );

  // Check to see if we already found a solution.
  var formNonce = getFormData( 'hashNonce' );
  if ( formNonce && checkNonce( hashDifficulty, hashStamp, formNonce ) ) {

    // We have a valid nonce; enable the form submit button
    document.querySelector('.rcm-loading').style.display = 'none';
    return true;
  }

  var nonce = 1;

  while( ! checkNonce( hashDifficulty, hashStamp, nonce ) ) {
    nonce++;
    if ( nonce % 10000 == 0 ) {
      let remaining = Math.round( ( Math.pow( 2, hashDifficulty ) - nonce ) / 10000 );
      document.getElementById( 'countdown' ).innerHTML = ' Processing. Please wait.';

      // Don't peg the CPU and prevent the browser from rendering these updates
      await sleep( 100 );
    }
  }

  document.querySelector('.hashNonce').value = nonce;

  // We have a valid nonce; enable the form submit button
  document.querySelector('.countdown').innerHTML = '';
  document.querySelector('.rcm-loading').style.display = 'none';

  return true;
}
