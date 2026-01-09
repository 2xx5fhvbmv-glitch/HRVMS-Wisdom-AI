// server.js (Node.js WebSocket Server)
const express = require('express');
const app = express();
const bodyParser = require('body-parser');
const cors = require("cors");

const server = require('http').createServer(app);
const io = require('socket.io')(server, {
    cors: {
        origin: "*", // Your Laravel app URL
        methods: ["GET", "POST"],
        credentials: true
    }
});

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cors());

const mobileNamespace = io.of("/mobile");

const connectedmobileUsers = {};
const missedNotifications = {};
const connectedUsers = {};


io.on("connection", (socket) => {
    console.log("User connected:", socket.id);
   
    socket.on("register-user", async (userId) => {
        // console.log("Received register-user event with userId:", userId);
        // console.log("Before update:", connectedUsers);

        if (userId) {
            connectedUsers[userId] = socket.id;
            // console.log("After update:", connectedUsers);

        } else {
            // console.log("Invalid userId received:", userId);
        }
    });
    socket.on("disconnect", () => {
        // console.log("User disconnected:", socket.id);
        Object.keys(connectedUsers).forEach(userId => {
            if (connectedUsers[userId] === socket.id) {
                delete connectedUsers[userId];
            }
        });
        // console.log("Updated connectedUsers after disconnect:", connectedUsers);
    });
    // Step 3: Listen for "send-message" and broadcast it
    socket.on("send-message", (data) => {
        console.log("📩 Received send-message event:", data); // Log incoming messages
    
        const { senderId, receiverId, message } = data;
    
        if (!receiverId || !senderId) {
            console.log("❌ Missing senderId or receiverId");
            return;
        }
    
        console.log(`✅ Message from ${senderId} to ${receiverId}: ${message}`);
        console.log("Current Connected Users:", connectedUsers);
    
        const receiverSocketId = connectedUsers[receiverId];
        
        console.log(receiverSocketId);
        if (receiverSocketId) {
            console.log(`📡 Sending message to ${receiverId} (Socket ID: ${receiverSocketId})`);
            io.to(receiverSocketId).emit("receive-message", data);
        } else {
            console.log(`❌ Receiver ${receiverId} is offline.`);
        }
    });

});

mobileNamespace.on("connection", (socket) => {
    console.log("User connected on mobile:", socket.id);
   
    socket.on("register-user", async (userId) => {
        // console.log("Received register-user event with userId:", userId);
        // console.log("Before update:", connectedUsers);

        if (userId) {
            connectedmobileUsers[userId] = socket.id;
            console.log("After update for mobile:", connectedmobileUsers);

            // Send any missed notifications to this user
            if (missedNotifications[userId]) {
                console.log(`Sending ${missedNotifications[userId].length} missed notifications to ${userId}`);
                missedNotifications[userId].forEach((notification) => {
                    socket.emit("new-mobile-notification", notification);
                });

                // Clear missed notifications after sending
                delete missedNotifications[userId];
            }
        } else {
            // console.log("Invalid userId received:", userId);
        }
    });
    socket.on("disconnect", () => {
        // console.log("User disconnected:", socket.id);
        Object.keys(connectedmobileUsers).forEach(userId => {
            if (connectedmobileUsers[userId] === socket.id) {
                delete connectedmobileUsers[userId];
            }
        });
        // console.log("Updated connectedUsers after disconnect:", connectedUsers);
    });
});

app.post("/notification", (req, res) => {
    // console.log("Received notification request:", req.body);
    console.log("Current connected users:", connectedUsers); // Debug all connected users

    // Extract user_id from the request
    const user_id = req.body.sendto?.toString(); // Convert to string for consistency
    if ( typeof user_id !== 'undefined' && user_id )
    {
            console.log(`Checking for user_id: ${user_id} in connectedUsers`);

            if (!user_id) {
                return res.status(400).json({ error: "User ID is required" });
            }

            const userSocketId = connectedUsers[user_id];

            if (userSocketId) 
            {
                io.to(userSocketId).emit("new-notification", req.body);
            } 
            else 
            {
                console.log(`❌ User ${user_id} not connected. Current Users:`, connectedUsers);
            }
                res.json({ success: true });
    }
    else
    {
        console.log("tes");
        io.emit('new-notification', req.body);

        res.sendStatus(200);
    }

});

app.post("/mob-send-notification", (req, res) => {
    console.log("Received notification request on mobile:", req.body);
    console.log("Current connected chat users:", connectedmobileUsers); // Debug all connected users
    console.log('req',req.body.sendto);
    
    const user_id = req.body.sendto?.toString(); 
    // Extract user_id from the request

    console.log(`Checking for user_id: ${user_id} in connectedmobileUsers`);

    if (!user_id) {
        return res.status(400).json({ error: "User ID is required" });
    }

    const mobileSocketId = connectedmobileUsers[user_id];

    // Send notification to Mobile connection
    if (mobileSocketId) {
        io.of("/mobile").to(mobileSocketId).emit("new-mobile-notification", req.body);
        console.log(`Sent to /mobile namespace: ${user_id}`);
    } else {
        console.log(`User ${user_id} not connected to /mobile. Storing missed notification.`);
        // Store the notification for later delivery
        if (!missedNotifications[user_id]) {
            missedNotifications[user_id] = [];
        }
        missedNotifications[user_id].push(req.body);
    }
    res.json({ success: true });
});

//for mobile
app.post("/sendChatMessage", (req, res) => {
    const { sender_id , receiver_id} = req.body;
    const senderId = String(sender_id); 

    if (!connectedmobileUsers[senderId]) {
        console.log(`Unauthorized attempt by ${sender_id}`);
        return res.status(403).json({ success: false, message: "Sender is not connected." });
    }

    if (!connectedmobileUsers[receiver_id]) {
        console.log(`Receiver ${receiver_id} is not connected.`);
        return res.status(404).json({ success: false, message: "Receiver is not connected, message stored in DB." });
    }

    const receiverSocketId = connectedmobileUsers[receiver_id];

    console.log("Broadcasting message to:", receiverSocketId);

    // Emit message to the specific receiver only
    mobileNamespace.to(receiverSocketId).emit("receiveMessage",req.body);

    res.status(200).json({ success: true, message: "Message sent to receiver." });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
});
