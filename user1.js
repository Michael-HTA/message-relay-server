const net = require('net');
const readline = require('readline');

const userId = 'user1';
const targetUserId = 'user2';

const client = new net.Socket();

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
  prompt: `${userId}> `
});

client.connect(9502, '127.0.0.1', () => {
  console.log(`Connected to server as ${userId}`);
  const registerMsg = JSON.stringify({ action: 'register', user_id: userId }) + '\n';
  client.write(registerMsg);
  rl.prompt();
});

client.on('data', (data) => {
  console.log(data.toString().trim());  // No \n prefix here
  rl.prompt();
});

client.on('close', () => {
  console.log('Connection closed');
  process.exit(0);
});

client.on('error', (err) => {
  console.error('Error:', err);
});

rl.on('line', (line) => {
  const trimmed = line.trim();
  if (trimmed.length === 0) {
    rl.prompt();
    return;
  }

  const msgObj = {
    action: 'message',
    to: targetUserId,
    message: trimmed
  };
  client.write(JSON.stringify(msgObj) + '\n');
});
