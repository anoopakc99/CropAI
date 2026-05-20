
<nav class="sb-topnav navbar navbar-expand navbar- bg-" style="
   background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
    <!-- Navbar Brand-->
    
    <a class="navbar-brand ps-3" href="{{ url('ccbf-admin') }}">
        <img src="{{ asset('logo/crop.png') }}" alt="Your App Logo" style="height: 45px;
    margin-left: 30px;">
       
    </a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <a class="nav-link d-flex align-items-center py-3 {{ request()->is('admin/dashboard') ? 'active' : '' }}" 
        href="{{ url('admin/dashboard') }}" 
        style="{{ request()->is('admin/dashboard') ? 'background-color: #4CAF50; color: white;' : 'color: #666;' }} border-radius: 8px; margin: 6 10px 5px 10px; padding-left: 15px;">
       
         <!-- <div style="color: #333;"><strong>Dashboard</strong></div> -->
     </a>
      <?php 
      $user = Auth::user(); 
    $site = DB::table('master_sites')->where('id', $user->site_id)->first();
    ?>
            <h5> {{ $site->site_name }} Central Cattle Breeding Farm</h5>
         
    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
    <div class="user-info">
    <div class="login-info">
        <strong>Last Login:</strong> 
        {{ Auth::user()->last_login 
            ? \Carbon\Carbon::parse(Auth::user()->last_login)
                ->setTimezone(config('app.timezone'))
                ->format('d M, Y h:i A') 
            : 'Never' 
        }}
    </div>
<div style="display: flex; align-items: center; gap: 10px;">
    <div style="background-color: olive; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
    </div>
    <span style="font-weight: 500;">{{ Auth::user()->name }}</span>
</div>


    <!--<div class="user-avatar">-->
    <!--    <i class="fas fa-cog"></i>-->
    <!--</div>-->
</div>

    </form>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ url('logout') }}" style="color: black;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</nav>
 <script>
//     window.addEventListener('DOMContentLoaded', function () {
//         const userName = @json(Auth::user()->name);
//         const welcomeMessage = `Welcome, ${userName} jee`;

//         function speakNow() {
//             const utterance = new SpeechSynthesisUtterance(welcomeMessage);
//             utterance.lang = 'hi-IN'; // Use 'en-US' if needed
//             utterance.rate = 1;

//             const voices = window.speechSynthesis.getVoices();
//             const selected = voices.find(v => v.lang === 'hi-IN' || v.lang === 'en-US' || v.name.includes('Google'));
//             if (selected) utterance.voice = selected;

//             window.speechSynthesis.speak(utterance);
//         }

//         if (window.speechSynthesis.getVoices().length > 0) {
//             speakNow();
//         } else {
//             window.speechSynthesis.onvoiceschanged = speakNow;
//         }
//     });
// </script>

