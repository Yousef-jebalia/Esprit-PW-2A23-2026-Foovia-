<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>FOOVIA â€” Custom Workouts</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="custome_workout_php.css">

</head>
<body>

<!-- NAV -->
<nav>
  <a href="#" class="nav-logo">
    <img src="assets/Plan de travail 1 no bg (3) (1).png" alt="FOOVIA Logo" style="height: 50px; width: auto;">
    FOOVIA
  </a>
  <ul class="nav-links">
    <li><a href="Exercice.php">Exercice</a></li>
    <li><a href="Workout.php">Workouts</a></li>
    <li><a href="custome_workout.php">Custom Workouts</a></li>
  </ul>
  <div class="nav-actions">
    <a href="backoffice.html" class="nav-btn nav-backoffice">Backoffice</a>
    <button class="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false">
      <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"></path>
      </svg>
      <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 1 0 11.5 11.5z"></path>
      </svg>
    </button>
    <a href="signin.html" class="nav-btn nav-signin">Sign In</a>
    <a href="signup.html" class="nav-btn nav-signup">Sign Up</a>
  </div>
</nav>






<!-- MAIN PAGE -->
<div class="cw-page">

  <!-- LEFT: intro copy -->
  <div class="cw-left">
    <span class="cw-eyebrow">Custom Workouts</span>
    <h1 class="cw-title">
      Build Your<br>
      <span class="accent">Perfect</span><br>
      <span class="accent2">Routine.</span>
    </h1>
    <p class="cw-desc">
      Design a workout that fits your goals â€” every rep, every set, every muscle.
      Go fully manual or let our AI build a smart plan tailored to the muscles you want to train.
    </p>
  </div>

  <!-- DIVIDER -->
  <div class="cw-divider"></div>

  <!-- RIGHT: action buttons -->
  <div class="cw-right">
    <p class="cw-right-label">Choose how to start</p>

    <!-- Manual button -->
    <button class="cw-choice-card manual-card" onclick="handleManual()">
      <div class="cw-card-icon">âœï¸</div>
      <div class="cw-card-body">
        <div class="cw-card-title">Build it Yourself</div>
        <div class="cw-card-sub">Pick your exercises, set your reps and rest times â€” full control over every detail.</div>
      </div>
      <svg class="cw-card-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 12h14M12 5l7 7-7 7"/>
      </svg>
    </button>

    <!-- AI button -->
    <button class="cw-choice-card ai-card" onclick="AI_workout_form()">
      <div class="cw-card-icon">ðŸ¤–</div>
      <div class="cw-card-body">
        <div class="cw-card-title">Generate with AI</div>
        <div class="cw-card-sub">Tell us your workout name and target muscles â€” our AI crafts the perfect plan for you.</div>
      </div>
      <svg class="cw-card-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 12h14M12 5l7 7-7 7"/>
      </svg>
    </button>
  </div>

</div>




<!-- AI WORKOUT FORM OVERLAY -->
<div class="ai-form-overlay" id="aiFormOverlay" onclick="closeOnOverlay(event)">
  <div class="ai-form-panel" id="aiFormPanel">

    <!-- close -->
    <button class="ai-form-close" onclick="closeAIForm()" aria-label="Close">
      <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>

    <!-- header -->
    <div class="ai-form-eyebrow">
      <span class="dot"></span> AI Generator
    </div>
    <h2 class="ai-form-title">Design Your Workout</h2>
    <p class="ai-form-subtitle">Name your session and pick the muscles you want to target â€” we'll handle the rest.</p>

    <!-- Workout name -->
    <div class="form-group">
      <label class="form-label" for="workoutName">Workout Name</label>
      <input
        class="form-input"
        type="text"
        id="workoutName"
        placeholder="e.g. Monday Push Day, Leg Destroyerâ€¦"
      />
      <div class="form-error" id="workoutNameError" aria-live="polite"></div>
    </div>

    <!-- Workout image -->
    <div class="form-group">
      <label class="form-label" for="work_picture">Workout Picture</label>
      <input
        class="form-input"
        type="file"
        id="work_picture"
        accept="image/*"
      />
      <p class="form-hint">Add an image for this workout so it appears in your workout list.</p>
      <div class="form-error" id="workPictureError" aria-live="polite"></div>
    </div>

    <!-- Muscle groups (chip select) -->
    <div class="form-group">
      <label class="form-label">Target Muscles</label>
      <div class="muscle-chips" id="muscleChips">
        <span class="muscle-chip" data-value="calves"      onclick="toggleChip(this)">Calves</span>
        <span class="muscle-chip" data-value="hamstrings"  onclick="toggleChip(this)">Hamstrings</span>
        <span class="muscle-chip" data-value="quadriceps"  onclick="toggleChip(this)">Quadriceps</span>
        <span class="muscle-chip" data-value="adductors"   onclick="toggleChip(this)">Adductors</span>
        <span class="muscle-chip" data-value="glutes"      onclick="toggleChip(this)">Glutes</span>
        <span class="muscle-chip" data-value="abs"         onclick="toggleChip(this)">Abs</span>
        <span class="muscle-chip" data-value="obliques"    onclick="toggleChip(this)">Obliques</span>
        <span class="muscle-chip" data-value="lower_back"  onclick="toggleChip(this)">Lower Back</span>
        <span class="muscle-chip" data-value="lats"        onclick="toggleChip(this)">Lats</span>
        <span class="muscle-chip" data-value="traps"       onclick="toggleChip(this)">Traps</span>
        <span class="muscle-chip" data-value="chest"       onclick="toggleChip(this)">Chest</span>
        <span class="muscle-chip" data-value="delts"       onclick="toggleChip(this)">Delts</span>
        <span class="muscle-chip" data-value="biceps"      onclick="toggleChip(this)">Biceps</span>
        <span class="muscle-chip" data-value="triceps"     onclick="toggleChip(this)">Triceps</span>
        <span class="muscle-chip" data-value="forearms"    onclick="toggleChip(this)">Forearms</span>
        <span class="muscle-chip" data-value="neck"        onclick="toggleChip(this)">Neck</span>
      </div>
      <p class="form-hint">Select one or more muscle groups to focus on.</p>
      <div class="form-error" id="muscleChipsError" aria-live="polite"></div>
    </div>

    <!-- Submit -->
    <button class="ai-form-submit" type="button" onclick="submitAIWorkout(this)">
      <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      Generate My Workout
    </button>

  </div>
</div>


<script>
  // Get user ID (you'll need to set this from your auth system)
  

  function showCopyModal(title, message) {//
    alert(title + '\n\n' + message);
  }

  // Load workouts on page load
  document.addEventListener('DOMContentLoaded', loadAIWorkouts);
  /* â”€â”€ THEME TOGGLE â”€â”€ */
  (function() {
    const root   = document.documentElement;
    const toggle = document.querySelector('.theme-toggle');
    const setTheme = (theme) => {
      const isDark = theme === 'dark';
      root.setAttribute('data-theme', theme);
      root.style.colorScheme = theme;
      toggle.setAttribute('aria-pressed', String(isDark));
      toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    };
    const stored      = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    setTheme(stored || (prefersDark ? 'dark' : 'light'));
    toggle.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      localStorage.setItem('theme', next);
      setTheme(next);
    });
  })();

  /* â”€â”€ MANUAL BUTTON â”€â”€ */
  function handleManual() {
    alert('This feature will be provided in the next version.');
  }

  /* â”€â”€ AI FORM OPEN / CLOSE â”€â”€ */
  function AI_workout_form() {
    document.getElementById('aiFormOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeAIForm() {
    document.getElementById('aiFormOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }

  function closeOnOverlay(e) {
    if (e.target === document.getElementById('aiFormOverlay')) closeAIForm();
  }

  /* â”€â”€ MUSCLE CHIP TOGGLE â”€â”€ */
  function toggleChip(el) {
    el.classList.toggle('selected');
    clearFieldError('muscleChips');
  }

  function clearFieldError(fieldKey) {
    const errorEl = document.getElementById(fieldKey + 'Error');
    if (errorEl) errorEl.textContent = '';

    const input = document.getElementById(fieldKey);
    if (input) {
      const group = input.closest('.form-group');
      if (group) group.classList.remove('error');
    }

    if (fieldKey === 'muscleChips') {
      const chips = document.getElementById('muscleChips');
      if (chips) chips.classList.remove('error');
    }
  }

  function setFieldError(fieldKey, message) {
    const errorEl = document.getElementById(fieldKey + 'Error');
    if (errorEl) errorEl.textContent = message;

    const input = document.getElementById(fieldKey);
    if (input) {
      const group = input.closest('.form-group');
      if (group) group.classList.add('error');
    }

    if (fieldKey === 'muscleChips') {
      const chips = document.getElementById('muscleChips');
      if (chips) chips.classList.add('error');
    }
  }

  function clearAIFormErrors() {
    ['workoutName', 'workPicture', 'muscleChips'].forEach(clearFieldError);
  }

  function validateAIWorkoutForm() {
    clearAIFormErrors();

    const nameInput = document.getElementById('workoutName');
    const workoutName = nameInput.value.trim();
    const selectedMuscles = [...document.querySelectorAll('.muscle-chip.selected')].map(c => c.dataset.value);
    const workoutPicture = document.getElementById('work_picture').files[0];

    let firstInvalidField = null;
    let errorMessage = '';

    if (!workoutName) {
      errorMessage = 'Workout name is required.';
      setFieldError('workoutName', errorMessage);
      firstInvalidField = firstInvalidField || nameInput;
    } else if (workoutName.length < 3) {
      errorMessage = 'Workout name must be at least 3 characters.';
      setFieldError('workoutName', errorMessage);
      firstInvalidField = firstInvalidField || nameInput;
    } else if (workoutName.length > 50) {
      errorMessage = 'Workout name must be 50 characters or less.';
      setFieldError('workoutName', errorMessage);
      firstInvalidField = firstInvalidField || nameInput;
    }

    if (selectedMuscles.length === 0) {
      errorMessage = errorMessage || 'Select at least one muscle group.';
      setFieldError('muscleChips', 'Select at least one muscle group.');
      firstInvalidField = firstInvalidField || document.getElementById('muscleChips');
    }

    if (workoutPicture) {
      const isImage = workoutPicture.type.startsWith('image/');
      const maxSize = 5 * 1024 * 1024;

      if (!isImage) {
        errorMessage = errorMessage || 'Workout picture must be an image file.';
        setFieldError('workPicture', 'Workout picture must be an image file.');
        firstInvalidField = firstInvalidField || document.getElementById('work_picture');
      } else if (workoutPicture.size > maxSize) {
        errorMessage = errorMessage || 'Workout picture must be 5 MB or smaller.';
        setFieldError('workPicture', 'Workout picture must be 5 MB or smaller.');
        firstInvalidField = firstInvalidField || document.getElementById('work_picture');
      }
    }

    if (firstInvalidField) {
      firstInvalidField.focus();
      return { valid: false, message: errorMessage || 'Please fix the form errors and try again.' };
    }

    return {
      valid: true,
      workoutName,
      selectedMuscles,
      workoutPicture
    };
  }

  /* â”€â”€ SUBMIT â”€â”€ */
  function submitAIWorkout(button) {
    const validated = validateAIWorkoutForm();
    if (!validated.valid) {
      alert(validated.message);
      return;
    }

    const { workoutName, selectedMuscles, workoutPicture } = validated;

    // Disable button during submission
    const btn = button;
    btn.disabled = true;
    btn.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle></svg> Generating...';

    const formData = new FormData();
    formData.append('workoutName', workoutName);
    formData.append('targetMuscles', JSON.stringify(selectedMuscles));
    formData.append('userId', userId);
    formData.append('aiService', 'gemini');

    if (workoutPicture) {
      formData.append('work_picture', workoutPicture);
    }

    // Call backend to generate and save workout
    fetch('../../../MVC/Controller/SPORT_MOULE/submit_ai_workout.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(text => {
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg> Generate My Workout';
      
      console.log('Response:', text);
      
      try {
        const data = JSON.parse(text);
        
        if (data.error) {
          showCopyModal('Workout Error', data.error);
          return;
        }

        alert(`âœ… Workout "${workoutName}" created successfully!`);
        closeAIForm();
        document.getElementById('workoutName').value = '';
        document.getElementById('work_picture').value = '';
        document.querySelectorAll('.muscle-chip').forEach(c => c.classList.remove('selected'));
        clearAIFormErrors();
        loadAIWorkouts();
      } catch (e) {
        showCopyModal('Invalid AI Response', text);
        console.error('Parse error:', e, 'Response:', text);
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg> Generate My Workout';
      showCopyModal('Network Error', err.message);
      console.error('Network Error:', err);
    });
  }

  /* â”€â”€ ESC to close â”€â”€ */
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAIForm();
  });
</script>

</body>
</html>
