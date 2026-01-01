<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content chat-page-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade h-100">
            <div class="row g-0 rounded-md-5 overflow-hidden shadow-lg glass-card chat-full-row">
                <!-- Contacts List -->
                <div class="col-md-4 border-end" id="contactsCol" style="background-color: var(--primary-teal);">
                    <div class="p-4 border-bottom border-secondary">
                        <h4 class="text-white fw-bold mb-0">Messages</h4>
                    </div>
                    <div id="contactsList" class="overflow-auto" style="height: calc(100% - 80px);">
                        <!-- Contacts will be loaded here -->
                        <div class="text-center text-white-50 mt-5">Loading contacts...</div>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="col-md-8 d-flex flex-column glass-chat-window d-none d-md-flex" id="chatCol"> 
                    <!-- Welcome Screen -->
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted" id="welcomeScreen">
                        <i class="fas fa-comments fa-4x mb-3 opacity-25"></i>
                        <p>Select a user to start messaging</p>
                    </div>

                    <!-- Active Chat Screen -->
                    <div class="d-flex flex-column h-100 d-none" id="chatScreen">
                        <div id="chat-header" class="p-4 border-bottom bg-white d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <button class="btn btn-light rounded-circle me-3 d-md-none" onclick="showContactList()">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <div id="active-contact-img" class="bg-light rounded-circle me-3" style="width: 45px; height: 45px;"></div>
                                <div>
                                    <h5 id="chatWith" class="fw-bold mb-0">Select a contact</h5>
                                    <small id="active-contact-status" class="text-muted">Online</small>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-light rounded-circle" onclick="startCall('video')"><i class="fas fa-video"></i></button>
                                <button class="btn btn-light rounded-circle ms-2" onclick="startCall('voice')"><i class="fas fa-phone"></i></button>
                            </div>
                        </div>

                        <div id="chatMessages" class="flex-grow-1 p-4 overflow-auto d-flex flex-column gap-3">
                            <!-- Messages go here -->
                        </div>

                        <div class="p-4 bg-white border-top">
                            <form id="messageForm" class="d-flex gap-2">
                                <input type="text" id="messageText" class="form-control rounded-pill px-4" placeholder="Type your message...">
                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Video Call Overlay -->
<div id="callOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark z-3 d-flex flex-column align-items-center justify-content-center">
    <div id="videoContainer" class="position-relative w-100 h-100">
        <video id="remoteVideo" class="w-100 h-100 bg-dark" autoplay playsinline style="object-fit: cover;"></video>
        <video id="localVideo" class="position-absolute bottom-0 end-0 m-4 rounded-3 border border-white" autoplay playsinline muted style="width: 200px; height: 150px; object-fit: cover;"></video>
        
        <div class="position-absolute bottom-0 start-50 translate-middle-x mb-5 d-flex gap-4">
            <button class="btn btn-danger btn-lg rounded-circle p-4" onclick="endCallUI()">
                <i class="fas fa-phone-slash fa-2x"></i>
            </button>
            <button id="toggleMic" class="btn btn-light btn-lg rounded-circle p-4">
                <i class="fas fa-microphone fa-2x"></i>
            </button>
            <button id="toggleVideo" class="btn btn-light btn-lg rounded-circle p-4">
                <i class="fas fa-video fa-2x"></i>
            </button>
        </div>
        
        <div class="position-absolute top-0 start-0 m-5 text-white">
            <h2 id="callingName">Calling...</h2>
            <p id="callStatus">Connecting...</p>
        </div>
    </div>
</div>

<!-- Incoming Call Pulse -->
<div id="incomingCallModal" class="d-none position-fixed bottom-0 end-0 m-4 p-4 rounded-5 shadow-lg bg-white z-max border animate-bounce" style="width: 350px; border: 2px solid var(--primary-teal);">
    <div class="d-flex align-items-center mb-3">
        <div id="incomingCallerImg" class="rounded-circle me-3" style="width: 60px; height: 60px; background: #eee;"></div>
        <div>
            <h5 class="fw-bold mb-0" id="incomingCallerName">Incoming Call</h5>
            <small class="text-muted">High-definition Audio/Video</small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-success flex-grow-1 rounded-pill py-2" onclick="answerCall()">
            <i class="fas fa-phone me-2"></i> Accept
        </button>
        <button class="btn btn-danger flex-grow-1 rounded-pill py-2" onclick="rejectCall()">
            <i class="fas fa-times me-2"></i> Decline
        </button>
    </div>
</div>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .glass-chat-window {
        background: rgba(255, 255, 255, 0.4);
    }
    
    @media (max-width: 991.98px) {
        .chat-page-content .welcome-msg {
            display: none !important;
        }
        .chat-page-content .topbar {
            margin-bottom: 1rem !important;
        }
    }

    .contact-item {
        transition: all 0.3s;
        cursor: pointer;
        margin: 5px 15px;
        border-radius: 15px;
    }
    .contact-item:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
    }
    
    .chat-full-row {
        height: calc(100vh - 160px);
    }
    
    #contactsCol, #chatCol {
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    @media (max-width: 991.98px) {
        body {
            height: 100vh;
            overflow: hidden;
        }
        .main-content.chat-page-content {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0 !important;
            overflow: hidden;
        }
        .chat-page-content .topbar {
            padding: 0.8rem 1rem;
            margin-bottom: 0 !important;
            flex-shrink: 0;
        }
        .chat-full-row {
            flex-grow: 1;
            height: auto !important;
            display: flex;
            flex-direction: column;
            border-radius: 0 !important;
            overflow: hidden;
        }
        #contactsCol, #chatCol {
            /* Inherited from global but ensured for mobile flex */
        }
        #contactsList {
            flex-grow: 1;
            overflow-y: auto;
        }
        #chatMessages {
            flex-grow: 1;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 20px;
        }
        #chat-header {
            padding: 0.6rem 1rem !important;
            flex-shrink: 0;
        }
        #chat-header h5 {
            font-size: 1rem;
        }
        #chat-header small {
            font-size: 0.7rem;
        }
        #active-contact-img {
            width: 35px !important;
            height: 35px !important;
            margin-right: 0.5rem !important;
        }
        #chat-header .btn {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
        }
        .bg-white.border-top.p-4 { /* The form container */
            padding: 0.8rem !important;
            flex-shrink: 0;
        }
        .container-fluid.p-0.animate-fade.h-100 {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            overflow: hidden;
        }
    }

    .contact-item.active {
        background: rgba(255, 255, 255, 0.25);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .message-bubble {
        max-width: 85%;
        padding: 12px 20px;
        border-radius: 20px;
        font-size: 0.95rem;
        word-wrap: break-word;
    }
    
    #chatMessages {
        scrollbar-width: thin;
        scrollbar-color: var(--primary-teal) transparent;
    }
    
    #chatMessages::-webkit-scrollbar {
        width: 6px;
    }
    
    #chatMessages::-webkit-scrollbar-thumb {
        background-color: var(--primary-teal);
        border-radius: 10px;
    }

    .msg-sent {
        align-self: flex-end;
        background: linear-gradient(135deg, var(--primary-teal), #2d7d74);
        color: white;
        border-bottom-right-radius: 5px;
        box-shadow: 0 4px 15px rgba(45, 125, 116, 0.2);
    }
    .msg-received {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(5px);
        color: var(--text-dark);
        border-bottom-left-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    /* Call UI Styles */
    #callOverlay {
        z-index: 2000;
    }
    #incomingCallModal {
        z-index: 2100;
    }
    .z-max {
        z-index: 9999;
    }
    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(45, 125, 116, 0.4); }
        70% { box-shadow: 0 0 0 20px rgba(45, 125, 116, 0); }
        100% { box-shadow: 0 0 0 0 rgba(45, 125, 116, 0); }
    }
    .animate-bounce {
        animation: pulse-border 2s infinite;
    }
    #localVideo {
        transform: scaleX(-1); /* Mirror local video */
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var currentUserId = <?php echo $user_id; ?>;
</script>
<script src="assets/js/chat.js?v=<?php echo time(); ?>"></script>
</body>
</html>
