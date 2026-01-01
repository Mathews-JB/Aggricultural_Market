<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Started | Muwowo Company</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <style>
        .role-btn {
            cursor: pointer;
            border: 2px solid #eee;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            text-align: center;
        }
        .role-radio:checked + .role-btn {
            border-color: var(--ingoude-primary);
            background: rgba(74, 140, 61, 0.05);
        }
        .role-radio:checked + .role-btn i {
            color: var(--ingoude-primary);
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid p-0 auth-wrapper">
    <div class="row g-0 w-100 min-vh-100">
        <!-- Visual Side -->
        <div class="col-lg-5 d-none d-lg-block auth-image-side" style="background-image: url('assets/img/auth_bg.png');">
            <div class="auth-image-overlay d-flex align-items-center justify-content-center text-white text-center p-5">
                <div class="animate-slide-up">
                    <h1 class="display-3 fw-bold mb-4">Join Us!</h1>
                    <p class="fs-5 opacity-75">"Empowering the local farming community with cutting-edge market technology."</p>
                </div>
            </div>
        </div>
        
        <!-- Form Side -->
        <div class="col-lg-7 d-flex align-items-center justify-content-center p-3 p-md-5">
            <div class="card auth-form-card w-100 p-4 p-md-5" style="max-width: 700px;">
                <div class="text-center mb-5">
                    <a href="index.php" class="text-decoration-none"><h2 class="fw-bold text-ingoude mb-1"><i class="fas fa-leaf me-2"></i> Muwowo</h2></a>
                    <p class="text-muted">Create your account and start trading today</p>
                </div>

                <form action="api/auth.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="row g-3 mb-4">
                        <h6 class="fw-bold mb-1">I am a...</h6>
                        <div class="col-md-6">
                            <input type="radio" name="role" value="farmer" id="role_farmer" class="d-none role-radio" required checked>
                            <label for="role_farmer" class="role-btn w-100">
                                <i class="fas fa-tractor fa-2x mb-2 text-muted"></i>
                                <div class="fw-bold">Farmer</div>
                                <small class="text-muted">Sell your produce</small>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" name="role" value="buyer" id="role_buyer" class="d-none role-radio" required>
                            <label for="role_buyer" class="role-btn w-100">
                                <i class="fas fa-shopping-basket fa-2x mb-2 text-muted"></i>
                                <div class="fw-bold">Buyer</div>
                                <small class="text-muted">Buy fresh food</small>
                            </label>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="full_name" class="form-control auth-input bg-light" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control auth-input bg-light" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone_number" class="form-control auth-input bg-light" placeholder="+260 9xx xxx xxx" required>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control auth-input bg-light" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control auth-input bg-light" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-check mb-5 ps-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label text-muted" for="terms">I agree to the <a href="#" class="text-ingoude underline">Terms of Service</a> and <a href="#" class="text-ingoude">Privacy Policy</a></label>
                    </div>

                    <button type="submit" class="btn btn-ingoude w-100 py-3 mb-4 fs-5">Create Account</button>
                    
                    <div class="text-center text-muted">
                        Already have an account? <a href="login.php" class="text-ingoude fw-bold text-decoration-none">Log in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
