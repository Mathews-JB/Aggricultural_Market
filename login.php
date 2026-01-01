<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Muwowo Company</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="bg-light">

<div class="container-fluid p-0 auth-wrapper">
    <div class="row g-0 w-100 min-vh-100">
        <!-- Visual Side -->
        <div class="col-lg-7 d-none d-lg-block auth-image-side" style="background-image: url('assets/img/auth_bg.png');">
            <div class="auth-image-overlay d-flex align-items-center justify-content-center text-white text-center p-5">
                <div class="animate-slide-up">
                    <h1 class="display-3 fw-bold mb-4">Welcome Back!</h1>
                    <p class="fs-5 opacity-75">"The future of agriculture is digital. Connect, trade, and grow with Ingoude Company."</p>
                </div>
            </div>
        </div>
        
        <!-- Form Side -->
        <div class="col-lg-5 d-flex align-items-center justify-content-center p-3 p-md-5">
            <div class="card auth-form-card w-100 p-4 p-md-5 <?php echo isset($_GET['error']) ? 'shake' : ''; ?>" style="max-width: 500px;">
                <div class="text-center mb-5">
                    <a href="index.php" class="text-decoration-none"><h2 class="fw-bold text-ingoude mb-1"><i class="fas fa-leaf me-2"></i> Muwowo</h2></a>
                    <p class="text-muted">Sign in to your account to continue</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger rounded-4 mb-4 border-0 shadow-sm">
                        Invalid email or password.
                    </div>
                <?php endif; ?>

                <form action="api/auth.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-pill ps-3"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control auth-input bg-light" placeholder="name@example.com" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex justify-content-between">
                            Password
                            <a href="#" class="text-ingoude text-decoration-none small">Forgot?</a>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 rounded-start-pill ps-3"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control auth-input bg-light" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="form-check mb-4 ps-4">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label text-muted" for="remember">Keep me signed in</label>
                    </div>
                    <button type="submit" class="btn btn-ingoude w-100 py-3 mb-4 fs-5">Sign In</button>
                    
                    <div class="text-center text-muted">
                        Don't have an account? <a href="register.php" class="text-ingoude fw-bold text-decoration-none">Sign up for free</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
