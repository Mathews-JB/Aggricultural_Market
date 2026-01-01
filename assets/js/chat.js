let selectedContact = null;
let selectedContactPubKey = null;
let myKeys = null;

// Call State
let localStream = null;
let peerConnection = null;
let currentCallId = null;
let isCaller = false;
let callInterval = null;

const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

// E2EE System using Web Crypto API
async function initE2EE() {
    console.log("Initializing E2EE...");
    const storedPub = localStorage.getItem('agrimarket_pub_' + currentUserId);
    const storedPriv = localStorage.getItem('agrimarket_priv_' + currentUserId);

    if (storedPub && storedPriv) {
        myKeys = {
            publicKey: await importKey(storedPub, 'public'),
            privateKey: await importKey(storedPriv, 'private')
        };
    } else {
        const keys = await window.crypto.subtle.generateKey(
            { name: "RSA-OAEP", modulusLength: 2048, publicExponent: new Uint8Array([1, 0, 1]), hash: "SHA-256" },
            true, ["encrypt", "decrypt"]
        );
        const exportedPub = await exportKey(keys.publicKey);
        const exportedPriv = await exportKey(keys.privateKey);
        localStorage.setItem('agrimarket_pub_' + currentUserId, exportedPub);
        localStorage.setItem('agrimarket_priv_' + currentUserId, exportedPriv);
        myKeys = keys;
        const formData = new FormData();
        formData.append('action', 'update_public_key');
        formData.append('public_key', exportedPub);
        await fetch('api/users.php', { method: 'POST', body: formData });
    }
}

async function exportKey(key) {
    const exported = await window.crypto.subtle.exportKey(key.type === 'public' ? "spki" : "pkcs8", key);
    return btoa(String.fromCharCode(...new Uint8Array(exported)));
}

async function importKey(keyStr, type) {
    const binaryDer = Uint8Array.from(atob(keyStr), c => c.charCodeAt(0));
    return await window.crypto.subtle.importKey(
        type === 'public' ? "spki" : "pkcs8", binaryDer,
        { name: "RSA-OAEP", hash: "SHA-256" }, true,
        type === 'public' ? ["encrypt"] : ["decrypt"]
    );
}

async function encryptData(text, publicKey) {
    const encoded = new TextEncoder().encode(text);
    const encrypted = await window.crypto.subtle.encrypt({ name: "RSA-OAEP" }, publicKey, encoded);
    return btoa(String.fromCharCode(...new Uint8Array(encrypted)));
}

async function decryptData(encryptedBase64, privateKey) {
    try {
        const encrypted = Uint8Array.from(atob(encryptedBase64), c => c.charCodeAt(0));
        const decrypted = await window.crypto.subtle.decrypt({ name: "RSA-OAEP" }, privateKey, encrypted);
        return new TextDecoder().decode(decrypted);
    } catch (e) {
        return "[Unable to decrypt messaging]";
    }
}

// UI and Navigation
function loadContacts() {
    const urlParams = new URLSearchParams(window.location.search);
    const forceUserId = urlParams.get('contact');
    const apiURL = forceUserId ? `api/messages.php?action=list_contacts&force_user_id=${forceUserId}` : 'api/messages.php?action=list_contacts';

    fetch(apiURL).then(res => res.json()).then(contacts => {
        const list = document.getElementById('contactsList');
        if (!list) return;
        list.innerHTML = '';
        if (!Array.isArray(contacts) || contacts.length === 0) {
            list.innerHTML = `<div class="text-white-50 text-center mt-5 p-3"><p>No conversations yet.</p></div>`;
            return;
        }
        contacts.forEach(c => {
            const activeClass = (selectedContact == c.id) ? 'active' : '';
            list.innerHTML += `
                <div class="contact-item p-2 p-md-3 d-flex align-items-center ${activeClass}" onclick="selectContact(${c.id}, '${c.full_name}', '${c.profile_image}', '${c.public_key || ''}')">
                    <img src="assets/img/${c.profile_image}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid white;" onerror="this.src='assets/img/default_profile.png'">
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold text-white" style="font-size: 0.9rem;">${c.full_name} <i class="fas fa-lock small opacity-50 ms-1"></i></h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">${c.role}</small>
                    </div>
                </div>`;
        });
        if (forceUserId && !selectedContact) {
            const target = contacts.find(c => c.id == forceUserId);
            if (target) selectContact(target.id, target.full_name, target.profile_image, target.public_key || '');
        }
    });
}

async function selectContact(id, name, image, pubKey) {
    selectedContact = id;
    selectedContactPubKey = pubKey ? await importKey(pubKey, 'public') : null;
    document.getElementById('chatWith').innerHTML = `${name} <span class="badge bg-success ms-2" style="font-size: 0.55rem;"><i class="fas fa-lock me-1"></i> E2EE</span>`;
    document.getElementById('welcomeScreen').classList.add('d-none');
    document.getElementById('chatScreen').classList.remove('d-none');
    document.getElementById('active-contact-img').innerHTML = `<img src="assets/img/${image}" class="w-100 h-100 rounded-circle" style="object-fit: cover;" onerror="this.src='assets/img/default_profile.png'">`;
    if (window.innerWidth < 768) {
        document.getElementById('contactsCol').classList.add('d-none');
        document.getElementById('chatCol').classList.remove('d-none');
    }
    loadMessages();
    loadContacts();
}

function loadMessages() {
    if (!selectedContact) return;
    fetch(`api/messages.php?action=fetch&other_id=${selectedContact}`).then(res => res.json()).then(async messages => {
        const container = document.getElementById('chatMessages');
        if (!container) return;
        container.innerHTML = '';
        for (const m of messages) {
            const isMine = m.sender_id == currentUserId;
            let displayMsg = m.message;
            if (m.message.length > 100) displayMsg = await decryptData(m.message, myKeys.privateKey);
            container.innerHTML += `
                <div class="d-flex ${isMine ? 'justify-content-end' : 'justify-content-start'} mb-3">
                    <div class="message-bubble shadow-sm ${isMine ? 'bg-primary text-white msg-sent' : 'bg-white msg-received'}">
                        <div class="message-text">${displayMsg}</div>
                        <div class="small opacity-50 text-end mt-1" style="font-size: 0.7rem;">
                            <i class="fas fa-shield-alt me-1"></i> ${new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                </div>`;
        }
        container.scrollTop = container.scrollHeight;
    });
}

function showContactList() {
    document.getElementById('contactsCol').classList.remove('d-none');
    document.getElementById('chatCol').classList.add('d-none');
    selectedContact = null;
}

// Calling Logic (WebRTC)
async function startCall(type) {
    if (!selectedContact) return;
    isCaller = true;
    document.getElementById('callOverlay').classList.remove('d-none');
    document.getElementById('callingName').innerText = "Calling " + document.getElementById('chatWith').innerText;

    // Secure Context Check
    if (!window.isSecureContext && window.location.hostname !== 'localhost') {
        alert("🚨 Security Error: Voice/Video calls require a secure connection (HTTPS) or localhost. Please ensure you are using 'localhost' in your browser address bar.");
        document.getElementById('callOverlay').classList.add('d-none');
        return;
    }

    try {
        const constraints = {
            video: type === 'video',
            audio: true
        };

        console.log("Requesting media with simple constraints:", constraints);
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        document.getElementById('localVideo').srcObject = localStream;

        peerConnection = new RTCPeerConnection(rtcConfig);
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

        peerConnection.onicecandidate = e => {
            if (e.candidate) addIceCandidate(e.candidate, 'caller');
        };

        peerConnection.ontrack = e => {
            document.getElementById('remoteVideo').srcObject = e.streams[0];
        };

        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);

        const formData = new FormData();
        formData.append('action', 'initiate');
        formData.append('receiver_id', selectedContact);
        formData.append('offer', JSON.stringify(offer));

        const res = await fetch('api/calls.php', { method: 'POST', body: formData }).then(r => r.json());
        currentCallId = res.call_id;

        startSignalingLoop();
    } catch (err) {
        console.error("Detailed Call Error:", err);
        let msg = "Could not access camera/microphone.";
        if (err.name === 'NotAllowedError') msg = "Permission Denied: Please allow camera/microphone access in your browser settings.";
        else if (err.name === 'NotFoundError') msg = "No Device Found: No camera or microphone detected.";
        else if (err.name === 'NotReadableError') msg = "Hardware Error: Device is already in use.";
        else if (err.name === 'OverconstrainedError') msg = "Constraint Error: Your device does not support the requested video quality.";
        else msg += ` (Error: ${err.name} - ${err.message})`;

        alert(msg);
        endCallUI();
    }
}

function startSignalingLoop() {
    callInterval = setInterval(async () => {
        if (!currentCallId) return;
        const res = await fetch(`api/calls.php?action=get_status&call_id=${currentCallId}`).then(r => r.json());

        if (res.status === 'active' && isCaller && !peerConnection.currentRemoteDescription && res.answer) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(JSON.parse(res.answer)));
        }

        if (res.status === 'rejected' || res.status === 'ended') {
            alert("Call " + res.status);
            endCallUI();
        }

        // Peer Candidates
        const candidates = res[isCaller ? 'receiver_candidates' : 'caller_candidates'];
        if (candidates) {
            const candList = JSON.parse(candidates);
            candList.forEach(c => peerConnection.addIceCandidate(new RTCIceCandidate(c)).catch(e => { }));
        }
    }, 2000);
}

async function answerCall() {
    document.getElementById('incomingCallModal').classList.add('d-none');
    document.getElementById('callOverlay').classList.remove('d-none');
    isCaller = false;

    try {
        const res = await fetch(`api/calls.php?action=get_status&call_id=${currentCallId}`).then(r => r.json());

        // Secure Context Check
        if (!window.isSecureContext && window.location.hostname !== 'localhost') {
            alert("🚨 Security Error: Voice/Video calls require a secure connection (HTTPS) or localhost.");
            endCallUI();
            return;
        }

        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        document.getElementById('localVideo').srcObject = localStream;

        peerConnection = new RTCPeerConnection(rtcConfig);
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

        peerConnection.onicecandidate = e => {
            if (e.candidate) addIceCandidate(e.candidate, 'receiver');
        };

        peerConnection.ontrack = e => {
            document.getElementById('remoteVideo').srcObject = e.streams[0];
        };

        await peerConnection.setRemoteDescription(new RTCSessionDescription(JSON.parse(res.offer)));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);

        const formData = new FormData();
        formData.append('action', 'accept');
        formData.append('call_id', currentCallId);
        formData.append('answer', JSON.stringify(answer));
        await fetch('api/calls.php', { method: 'POST', body: formData });

        startSignalingLoop();
    } catch (err) {
        console.error("Answer Error:", err);
        alert(`Could not access camera/microphone to answer call. (Error: ${err.name})`);
        endCallUI();
    }
}

async function rejectCall() {
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('call_id', currentCallId);
    await fetch('api/calls.php', { method: 'POST', body: formData });
    document.getElementById('incomingCallModal').classList.add('d-none');
}

function addIceCandidate(candidate, type) {
    const formData = new FormData();
    formData.append('action', 'add_candidate');
    formData.append('call_id', currentCallId);
    formData.append('type', type);
    formData.append('candidate', JSON.stringify(candidate));
    fetch('api/calls.php', { method: 'POST', body: formData });
}

function endCallUI() {
    clearInterval(callInterval);
    if (localStream) localStream.getTracks().forEach(t => t.stop());
    if (peerConnection) peerConnection.close();

    if (currentCallId) {
        const formData = new FormData();
        formData.append('action', 'end');
        formData.append('call_id', currentCallId);
        fetch('api/calls.php', { method: 'POST', body: formData });
    }

    document.getElementById('callOverlay').classList.add('d-none');
    document.getElementById('remoteVideo').srcObject = null;
    document.getElementById('localVideo').srcObject = null;
    currentCallId = null;
    peerConnection = null;
}

// Background poller
function initPollers() {
    setInterval(() => {
        if (selectedContact) loadMessages();
    }, 4000);

    setInterval(async () => {
        if (currentCallId) return; // Already in a call
        const res = await fetch('api/calls.php?action=check_incoming').then(r => r.json());
        if (res.id) {
            currentCallId = res.id;
            document.getElementById('incomingCallerName').innerText = res.full_name;
            document.getElementById('incomingCallerImg').style.backgroundImage = `url(assets/img/${res.profile_image})`;
            document.getElementById('incomingCallerImg').style.backgroundSize = 'cover';
            document.getElementById('incomingCallModal').classList.remove('d-none');
        }
    }, 3000);
}

const messageForm = document.getElementById('messageForm');
if (messageForm) {
    messageForm.onsubmit = async (e) => {
        e.preventDefault();
        const input = document.getElementById('messageText');
        if (!input.value.trim() || !selectedContact) return;
        const finalMsg = selectedContactPubKey ? await encryptData(input.value, selectedContactPubKey) : input.value;
        const formData = new FormData();
        formData.append('receiver_id', selectedContact);
        formData.append('message', finalMsg);
        fetch('api/messages.php?action=send', { method: 'POST', body: formData }).then(() => {
            input.value = '';
            loadMessages();
        });
    };
}

window.addEventListener('DOMContentLoaded', () => {
    loadContacts();
    initE2EE();
    initPollers();
});

window.selectContact = selectContact;
window.showContactList = showContactList;
window.startCall = startCall;
window.answerCall = answerCall;
window.rejectCall = rejectCall;
window.endCallUI = endCallUI;
