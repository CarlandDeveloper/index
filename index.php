<?php
// --- PHP-LOGIK FÖR FORMULÄRHANTERING ---

// Kontrollera om förfrågan är en POST-förfrågan (dvs. från vårt JS-formulär)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sätt headers för att indikera att vi svarar med JSON och använder UTF-8
    header('Content-Type: application/json; charset=utf-8');

    // Läs den inkommande JSON-datan från fetch-anropet
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Validering på serversidan (Viktigt! Lita aldrig enbart på klient-validering)
    if (
        !isset($data['name']) || empty(trim($data['name'])) ||
        !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
        !isset($data['phone']) || empty(trim($data['phone'])) ||
        !isset($data['company']) || empty(trim($data['company'])) ||
        !isset($data['digitalScore']) || !isset($data['scoreTitle'])
    ) {
        // Skicka ett felmeddelande om data saknas eller är felaktig
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Vänligen fyll i alla obligatoriska fält korrekt.']);
        exit(); // Avsluta skriptet
    }

    // Sanera indata för säkerhets skull
    $name = htmlspecialchars(trim($data['name']));
    $email = htmlspecialchars(trim($data['email']));
    $phone = htmlspecialchars(trim($data['phone']));
    $company = htmlspecialchars(trim($data['company']));
    $digitalScore = htmlspecialchars($data['digitalScore']);
    $scoreTitle = htmlspecialchars($data['scoreTitle']);
    
    // NYTT: Hantera det valfria meddelandefältet
    $userMessage = 'Inget meddelande lämnades.';
    if (isset($data['message']) && !empty(trim($data['message']))) {
        $userMessage = htmlspecialchars(trim($data['message']));
    }
    
    // --- SKAPA OCH SKICKA E-POSTMEDDELANDET ---
    
    $to = 'davidhenricson.swe@gmail.com';
    $subject = 'Nytt Lead från Digitaliserings-Score: ' . $company;

    // Skapa en snygg och strukturerad meddelandekropp
    $messageBody = "Ett nytt lead har genererats via Digitaliserings-Score-testet.\n\n";
    $messageBody .= "============================================\n";
    $messageBody .= "KUNDUPPGIFTER\n";
    $messageBody .= "============================================\n";
    $messageBody .= "Företag: " . $company . "\n";
    $messageBody .= "Kontaktperson: " . $name . "\n";
    $messageBody .= "E-post: " . $email . "\n";
    $messageBody .= "Telefon: " . $phone . "\n\n";
    $messageBody .= "--------------------------------------------\n";
    $messageBody .= "MEDDELANDE FRÅN ANVÄNDAREN\n";
    $messageBody .= "--------------------------------------------\n";
    $messageBody .= $userMessage . "\n\n"; // Inkludera meddelandet här
    $messageBody .= "============================================\n";
    $messageBody .= "QUIZ-RESULTAT\n";
    $messageBody .= "============================================\n";
    $messageBody .= "Digitaliserings-Score (1-15): " . $digitalScore . "\n";
    $messageBody .= "Resultat-titel: " . $scoreTitle . "\n\n";
    $messageBody .= "Vänligen följ upp detta lead omgående.\n";

    // Headers är viktiga för att mejlet ska komma fram och se korrekt ut
    $headers = 'From: AvistaTime Quiz <noreply@dindoman.com>' . "\r\n" .
               'Reply-To: ' . $email . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    // Skicka meddelandet
    if (mail($to, $subject, $messageBody, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Meddelandet har skickats!']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Serverfel. Meddelandet kunde inte skickas.']);
    }

    exit(); // Avsluta skriptet här så att HTML-koden nedan inte renderas
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digitaliserings-Score för Städ & Service | AvistaTime</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GRUNDLÄGGANDE STYLING & VARIABLER --- */
        :root {
            --primary-color: #00529B; /* AvistaTime Blue */
            --secondary-color: #00A9E0; /* Lighter Blue */
            --accent-color: #FDB913; /* Yellow/Gold Accent */
            --dark-text: #2c3e50;
            --light-text: #ffffff;
            --background-color: #f4f7f6;
            --container-bg: #ffffff;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--dark-text);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* --- SIDCONTAINER & SEKTIONER --- */
        .container {
            width: 100%;
            max-width: 800px;
            background-color: var(--container-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            text-align: center;
            margin-top: 20px;
        }

        .section {
            padding: 40px;
            display: none; /* Dölj alla sektioner som standard */
        }

        .section.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out forwards;
        }

        .section.fading-out {
            animation: fadeOut 0.3s ease-in-out forwards;
        }

        .section-title {
            color: var(--primary-color);
        }
        
        [tabindex="-1"]:focus {
            outline: none;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 20px;
            height: auto;
        }

        /* --- HJÄLTESEKTION (START) --- */
        #hero-section h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        #hero-section p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 30px auto;
        }

        /* --- KNAPPAR --- */
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-3px);
            background-color: var(--secondary-color);
        }
        
        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .btn-accent {
            background-color: var(--accent-color);
            color: var(--dark-text);
        }
        .btn-accent:hover:not(:disabled) {
            background-color: #fcc43b;
        }

        /* --- QUIZ-SEKTION --- */
        #progress-bar-container {
            width: 100%;
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        #progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            border-radius: 5px;
            transition: width 0.4s ease-in-out;
        }
        
        #question-counter {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 10px;
        }

        #question-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 30px;
            min-height: 90px;
        }

        .answer-options {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .answer-btn {
            width: 100%;
            background-color: var(--container-bg);
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: left;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--dark-text);
        }

        .answer-btn:hover:not(:disabled) {
            border-color: var(--secondary-color);
            background-color: #e9f6fe;
            transform: translateX(5px);
        }
        
        .answer-btn:focus {
            outline: 2px solid var(--primary-color);
        }

        .answer-btn:disabled {
            cursor: default;
            opacity: 0.7;
        }

        /* --- RESULTATSEKTION --- */
        .score-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--background-color);
            border: 8px solid var(--primary-color);
            animation: score-pop 0.6s ease-out;
        }
        
        #score-display {
            font-size: 4rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        #score-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        #score-explanation {
            font-size: 1rem;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* --- FORMULÄR --- */
        .form-container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: var(--border-radius);
            border: 1px solid #eee;
            margin-top: 20px;
        }
        
        .form-container h3 {
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea { /* NYTT: Inkluderar textarea här */
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            resize: vertical; /* Tillåter vertikal omskalning */
        }
        
        .form-group input:focus,
        .form-group textarea:focus { /* NYTT: Inkluderar textarea här */
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 82, 155, 0.2);
        }
        
        #form-error {
            color: var(--error-color);
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--error-color);
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        /* --- "VARFÖR AVISTATIME"-SEKTION --- */
        #why-avista-section {
            padding: 50px 40px;
            background-color: var(--background-color);
            text-align: center;
        }
        
        .features {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 30px;
        }
        
        .feature {
            flex-basis: 220px;
        }
        
        .feature .icon {
            height: 48px;
            width: 48px;
            margin: 0 auto 15px auto;
        }
        
        .feature h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        footer {
            margin-top: 40px;
            font-size: 0.9rem;
            color: #777;
            text-align: center;
        }
        
        /* --- ANIMATIONER & RESPONSIVITET --- */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-15px); }
        }

        @keyframes score-pop {
            0% { transform: scale(0.5); opacity: 0; }
            80% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { margin-top: 10px; }
            .section { padding: 25px; }
            
            #hero-section h1 { font-size: 2rem; }
            #hero-section p { font-size: 1rem; }
            
            #question-text { font-size: 1.3rem; min-height: 100px; }
            .answer-btn { padding: 15px; }

            .features {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- ==================== HJÄLTESEKTION ==================== -->
        <section id="hero-section" class="section active">
            <img src="https://static.wixstatic.com/media/2432be_fa180bd7fcb14e63afeec97d095b36d4~mv2.png" alt="AvistaTime" class="logo">
            <h1 id="hero-title" class="section-title" tabindex="-1">Hur digital är din städ- och serviceverksamhet?</h1>
            <p>Svara på 5 snabba frågor och få ert personliga Digitaliserings-Score direkt. Upptäck var ni kan spara tid, öka lönsamheten och få nöjdare kunder.</p>
            <button id="start-quiz-btn" class="btn btn-accent">Starta testet nu! (Gratis)</button>
        </section>

        <!-- ==================== QUIZ-SEKTION ==================== -->
        <section id="quiz-section" class="section">
            <div id="progress-bar-container">
                <div id="progress-bar"></div>
            </div>
            <div id="question-counter"></div>
            <h2 id="question-text" class="section-title" tabindex="-1">Frågetext här...</h2>
            <div id="answer-options" class="answer-options">
                <!-- Svarsalternativ genereras av JavaScript -->
            </div>
        </section>
        
        <!-- ==================== RESULTATSEKTION MED FORMULÄR ==================== -->
        <section id="result-section" class="section">
            <h2 id="result-title" class="section-title" tabindex="-1">Ert Digitaliserings-Score är:</h2>
            <div class="score-container">
                <span id="score-display">0</span>
            </div>
            <p id="score-text"></p>
            <p id="score-explanation"></p>
            
            <div class="form-container">
                <h3>Ta nästa steg mot full kontroll!</h3>
                <p style="margin-bottom: 20px;">Fyll i dina uppgifter så bokar vi en <strong>kostnadsfri och anpassad demo</strong> där vi visar exakt hur AvistaTime kan lösa era utmaningar.</p>
                <form id="lead-form" method="POST" novalidate>
                    <div class="form-group">
                        <label for="name">Namn</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-postadress</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefonnummer</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="company">Företag/Organisation</label>
                        <input type="text" id="company" name="company" required>
                    </div>
                    <!-- NYTT: Meddelandefältet har lagts till här -->
                    <div class="form-group">
                        <label for="message">Meddelande (valfritt)</label>
                        <textarea id="message" name="message" rows="4"></textarea>
                    </div>
                    <button type="submit" id="submit-btn" class="btn btn-accent" style="width: 100%;">Boka min kostnadsfria demo nu!</button>
                    <p id="form-error"></p>
                </form>
            </div>
        </section>
        
        <!-- ==================== TACK-SEKTION ==================== -->
        <section id="thank-you-message" class="section">
             <h2 id="thank-you-title" class="section-title" tabindex="-1">Tack för din förfrågan!</h2>
             <p>Vi har tagit emot dina uppgifter och en av våra experter kommer att kontakta dig inom kort för att boka in er personliga demo. Vi ser fram emot att visa er framtiden inom lokalvård!</p>
        </section>

    </div>
    
    <!-- ==================== "VARFÖR AVISTATIME"-SEKTION (ALLTID SYNLIG) ==================== -->
    <div id="why-avista-section">
        <h2>Med AvistaTime får du...</h2>
        <div class="features">
            <div class="feature">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color: var(--primary-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <h3>Full kontroll i realtid</h3>
                <p>Se exakt var personalen är, vad som är gjort och vad som återstår.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color: var(--primary-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                </div>
                <h3>Ökad lönsamhet</h3>
                <p>Automatisera tidrapporter och fakturaunderlag. Minska administrativ tid.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color: var(--primary-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75s.168-.75.375-.75.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" /></svg>
                </div>
                <h3>Nöjdare kunder</h3>
                <p>Leverera professionella rapporter och garantera kvalitet i varje uppdrag.</p>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2024 Avista Time AB. En smartare väg till digitalisering.</p>
    </footer>

    <script>
    (() => {
        // --- DATA: FRÅGOR OCH SVAR ---
        const quizData = [
            { question: "Hur hanterar ni tidrapportering och närvarokontroll idag?", options: [{ text: "Med papper och penna, eller kanske i ett Excel-ark.", points: 1 },{ text: "Vi använder en enkel digital app, men den är inte kopplad till lön eller fakturering.", points: 2 },{ text: "Vi har ett system med digital stämpelklocka (mobil/NFC) som automatiskt skapar löne- och fakturaunderlag.", points: 3 }] },
            { question: "Hur skickas arbetsordrar och scheman ut till er personal?", options: [{ text: "Via SMS, telefonsamtal eller utskrivna listor.", points: 1 },{ text: "Vi använder en delad digital kalender (t.ex. Google Calendar), men ändringar är manuella.", points: 2 },{ text: "Allt finns i ett centralt system. Personalen ser sina uppdrag direkt i mobilen med all info de behöver.", points: 3 }] },
            { question: "Hur säkerställer och dokumenterar ni kvaliteten på utfört arbete?", options: [{ text: "Vi gör stickprovskontroller när tid finns, oftast baserat på magkänsla.", points: 1 },{ text: "Vi använder digitala checklistor, men rapportering och uppföljning sker manuellt.", points: 2 },{ text: "Vi har digitala kvalitetskontroller med bilder och signaturer som skickas direkt till kund och sparas i systemet.", points: 3 }] },
            { question: "Hur kommunicerar ni med era kunder om utförda jobb och eventuella avvikelser?", options: [{ text: "Främst via telefon och mejl, och oftast bara när ett problem uppstår.", points: 1 },{ text: "Vi skickar regelbundna uppdateringar, men det är en manuell process att sammanställa rapporterna.", points: 2 },{ text: "Kunder kan se status i realtid via en kundportal och får automatiska rapporter. Avvikelser hanteras direkt i systemet.", points: 3 }] },
            { question: "Hur ser processen ut från utfört jobb till skickad faktura?", options: [{ text: "Helt manuellt. Vi samlar in tidrapporter, räknar ihop timmar och skriver fakturor för hand.", points: 1 },{ text: "Delvis digitalt. Tidrapporter är digitala, men måste manuellt föras över till faktureringsprogrammet.", points: 2 },{ text: "Helautomatiskt. Godkända tidrapporter blir direkt till ett fakturaunderlag med ett klick.", points: 3 }] }
        ];

        // --- DOM-ELEMENT ---
        const sections = document.querySelectorAll('.section');
        const startQuizBtn = document.getElementById('start-quiz-btn');
        const questionText = document.getElementById('question-text');
        const answerOptionsContainer = document.getElementById('answer-options');
        const progressBar = document.getElementById('progress-bar');
        const questionCounter = document.getElementById('question-counter');
        const scoreDisplay = document.getElementById('score-display');
        const scoreText = document.getElementById('score-text');
        const scoreExplanation = document.getElementById('score-explanation');
        const leadForm = document.getElementById('lead-form');
        const submitBtn = document.getElementById('submit-btn');
        const formError = document.getElementById('form-error');

        // --- QUIZ-LOGIK ---
        let currentQuestionIndex = 0;
        let userScore = 0;
        let activeSection = document.querySelector('.section.active');
        
        // --- FUNKTIONER ---

        function showSection(sectionId) {
            const nextSection = document.getElementById(sectionId);
            if (!nextSection) return;

            if (activeSection) {
                activeSection.classList.add('fading-out');
                activeSection.addEventListener('animationend', () => {
                    activeSection.classList.remove('active', 'fading-out');
                    nextSection.classList.add('active');
                    const title = nextSection.querySelector('.section-title');
                    if (title) title.focus();
                    activeSection = nextSection;
                }, { once: true });
            } else {
                nextSection.classList.add('active');
                activeSection = nextSection;
            }
        }

        function startQuiz() {
            showSection('quiz-section');
            displayQuestion();
        }

        function displayQuestion() {
            answerOptionsContainer.innerHTML = ''; 
            
            const currentQuestion = quizData[currentQuestionIndex];
            
            questionText.textContent = currentQuestion.question;
            questionCounter.textContent = `Fråga ${currentQuestionIndex + 1} av ${quizData.length}`;
            updateProgressBar();

            currentQuestion.options.forEach(option => {
                const button = document.createElement('button');
                button.textContent = option.text;
                button.classList.add('answer-btn');
                button.onclick = () => selectAnswer(option.points);
                answerOptionsContainer.appendChild(button);
            });
        }
        
        function selectAnswer(points) {
            document.querySelectorAll('.answer-btn').forEach(btn => btn.disabled = true);
            userScore += points;
            currentQuestionIndex++;
            
            setTimeout(() => {
                if (currentQuestionIndex < quizData.length) {
                    displayQuestion();
                } else {
                    showResults();
                }
            }, 400);
        }
        
        function updateProgressBar() {
            const progressPercentage = (currentQuestionIndex / quizData.length) * 100;
            progressBar.style.width = `${progressPercentage}%`;
        }

        function showResults() {
            progressBar.style.width = `100%`;
            
            const minPossibleScore = quizData.length * 1;
            const maxPossibleScore = quizData.length * 3;
            const finalScore = Math.round(((userScore - minPossibleScore) / (maxPossibleScore - minPossibleScore)) * 9) + 1;
            
            scoreDisplay.textContent = finalScore;
            
            let resultTitle, resultExplanation, scoreColor;
            
            if (finalScore <= 3) {
                resultTitle = "Digital Nybörjare";
                resultExplanation = "Er verksamhet förlitar sig mycket på manuella processer. Här finns en ENORM potential att spara tid, minska fel och öka lönsamheten. Ett system som AvistaTime skulle revolutionera er vardag.";
                scoreColor = 'var(--error-color)';
            } else if (finalScore <= 7) {
                resultTitle = "På God Väg";
                resultExplanation = "Ni har börjat digitalisera, vilket är jättebra! Utmaningen är ofta att olika system inte pratar med varandra. Genom att samla allt i ett system som AvistaTime kan ni få full kontroll och eliminera dubbelarbete.";
                scoreColor = 'var(--accent-color)';
            } else {
                resultTitle = "Digital Mästare";
                resultExplanation = "Imponerande! Ni ligger i framkant. Er utmaning är inte att digitalisera, utan att optimera. Låt oss visa hur AvistaTime kan finslipa era processer, ge er ännu bättre data och lyfta kundupplevelsen till nästa nivå.";
                scoreColor = 'var(--success-color)';
            }
            
            scoreText.textContent = resultTitle;
            scoreExplanation.textContent = resultExplanation;
            scoreDisplay.parentElement.style.borderColor = scoreColor;
            scoreDisplay.style.color = scoreColor;
            
            showSection('result-section');
        }
        
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            if (!leadForm.checkValidity()) {
                formError.textContent = "Vänligen fyll i alla obligatoriska fält korrekt.";
                formError.style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Skickar...';
            formError.style.display = 'none';

            const formData = new FormData(leadForm);
            const leadData = {
                name: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                company: formData.get('company'),
                message: formData.get('message'), // NYTT: Hämtar meddelandet
                digitalScore: userScore,
                scoreTitle: scoreText.textContent
            };

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(leadData),
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Serverfel: ${response.statusText}`);
                }

                showSection('thank-you-message');

            } catch (error) {
                console.error('Form submission error:', error);
                formError.textContent = error.message || "Något gick fel. Kontrollera din anslutning och försök igen.";
                formError.style.display = 'block';
            } finally {
                if (document.getElementById('submit-btn')) { 
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Boka min kostnadsfria demo nu!';
                }
            }
        }

        // --- EVENT LISTENERS ---
        startQuizBtn.addEventListener('click', startQuiz);
        leadForm.addEventListener('submit', handleFormSubmit);
    })();
    </script>

</body>
</html>
