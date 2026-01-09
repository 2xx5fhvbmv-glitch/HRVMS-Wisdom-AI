const { io } = require("socket.io-client");

// Connect to WebSocket server
// const socket = io("ws://projects.spaculus.live/3/wisdomAI:3000/chat");
const socketSender = io("http://localhost:3000/mobile", {
    transports: ["websocket"]
});
const socketReceiver = io("http://localhost:3000/mobile", {
    transports: ["websocket"]
});

// Register sender (User 26)
socketSender.on("connect", () => {
    console.log(`Sender Connected: ${socketSender.id}`);
    socketSender.emit("register-user", 26);
});

// Register receiver (User 31)
socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 23);
});
// Register receiver (User 31)
socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 26);
});

// Register receiver (HOD - Adam 61)
socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 61);
});

// Register receiver (User 31)
socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 26);
});

socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 24);
});

// Register receiver (Excom-Nisha 84)
socketReceiver.on("connect", () => {
    console.log(`Receiver Connected: ${socketReceiver.id}`);
    socketReceiver.emit("register-user", 84);
});

// Listen for messages on receiver
socketReceiver.on("receiveMessage", (message) => {
    console.log("Message Received by Receiver:", message);
});

socketReceiver.on("new-mobile-notification", (data) => {
    console.log("New Notification on mobile:", data);
});
