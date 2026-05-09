import { verifyPersonalMessageSignature } from '@mysten/sui/verify';

const [, , messageB64, signature, address] = process.argv;

async function main() {
  if (!messageB64 || !signature || !address) {
    console.error('Usage: verify-sui-personal-message.mjs <messageBase64> <signature> <suiAddress>');
    process.exit(1);
  }

  const message = Uint8Array.from(Buffer.from(messageB64, 'base64'));
  await verifyPersonalMessageSignature(message, signature, { address });
  process.stdout.write('1\n');
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
