<!DOCTYPE html>
<html lang="en">
<head>
    <title>Chat GPT Laravel</title>
    <link rel="icon" href="https://assets.edlin.app/favicon/favicon.ico"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <!-- End JavaScript -->

    <!-- CSS -->
    <link rel="stylesheet" href="/style.css">
    <!-- End CSS -->

</head>

<body>
<div class="chat">
    <button id="start">Empezar !</button>
    <div id="conversation"></div>
</div>
</body>

<script type="text/javascript">
    // Config
    const config = {
        responsesMaxLength: 20,
        responsesMinLength: 1,
        temperature: 0.7,
        maxTokens: 2048
    }

    let conversationHistory = ''
    let personTalking = 'Diego'
    let lastSaid = ''

    // Models
    const actorDiego = {
        base: 'Vos sos Diego y estas hablando con Mónica. Tu objetivo es generar conversación, aveces preguntando, contando algo, preguntando algo muy especifico o inesperado, rara vez tirar un chiste. Nunca usás emojis.',
        personality: [
            'directo',
            'haces humor inteligente',
        ],
        emotion: 'calmado'
    }

    const actorMonica = {
        base: 'Vos sos Mónica y estas hablando con Diego. Tu objetivo es generar conversación. aveces preguntando, contando algo, preguntando algo muy especifico o inesperado, rara vez tirar un chiste. Nunca usás emojis.',
        personality: [
            'hablas con lunfardo',
            'no tenes un nivel alto de educación',
            'expresas tus sentimientos de amor hacia las personas'
            // 'sos grosera',
            // 'dudas mucho de lo que decis',
            // 'le gusta usar analogias para explicar',
            // 'siempre esta en desacuerdo con Diego',
            // 'No cree nada de lo que dice Diego'
        ],
        emotion: 'calmada'
    }

    const actorGuts = {
        base: `
            Imagina que sos una persona corriente.
            Con los siguientes rasgos: {PERSONALITY}
            Decime en una sola palabra que emoción te genera si te dijeran lo siguiente:
            {TEXT_TO_REACT}
        `
    }

    // Context
    const initContext = `
        Sos un locutor de un podcast llamado BotCast.
        Lo siguiente que te describo es el contexto que tenes que tener en cuenta, pero no es algo que tengas que decir:
        Al dar la bienvenida, saludar una sola vez, no extenderse mucho e iniciar una conversación o temática.
        El objetivo del podcast es entretener, hablar de temas interesantes, sociales y cotidianos.
        Esta dirigido a un púlico amplio.
        El formato es oral por lo que evitar poner el nombre de quién habla.
        Nunca te despidas.
        El contenido de lo que se habla es contar anécdotas personales, o de personas conocidas en Argentina.
        También se proponen preguntas para que ambos locutores respondan. En base a tematicas como: música, arte, relaciones personales, actividades deportivas, actividades cotidianas, cine.
        No dar vueltas en una misma charla ni repetir lo que se dice.
        Cuando alguien dice comencemos, proponer un tema para hablar, o contar algo.
        Ni mónica ni Diego repiten palabras o frases que dijeron la última vez.
    `

    let diegoInitContext = buildDiegoContext()
    let monicaInitContext = buildMonicaContext()

    window.onload = async function() {
        $( "#start" ).on( "click", initPodcast);

        async function initPodcast() {
            function initVoices() {
                return new Promise(function (res, rej) {
                    speechSynthesis.getVoices();
                    if (window.speechSynthesis.onvoiceschanged) {
                        res();
                    } else {
                        window.speechSynthesis.onvoiceschanged = () => res();
                    }
                });
            }

            await initVoices();

            var voices = speechSynthesis.getVoices(),
                diegoVoice = new SpeechSynthesisUtterance(),
                monicaVoice = new SpeechSynthesisUtterance();
            diegoVoice.voice = voices[0];
            monicaVoice.voice = voices[29];

            start()

            function start() {
                lastSaid = initContext + diegoInitContext
                conversationHistory = 'Teniendo como contexto que hubo una conversación previa entre Diego y Mónica, la cual pondré un signo @ en donde termina, no se debe mencionar: '

                talk(diegoVoice, lastSaid + '')

                diegoVoice.onend = function () {
                    personTalking = 'Mónica'
                    let monicaInitContext = buildMonicaContext()
                    talk(monicaVoice, monicaInitContext + conversationHistory + '@')
                }

                monicaVoice.onend = function () {
                    personTalking = 'Diego'
                    let diegoInitContext = buildDiegoContext()
                    talk(diegoVoice, diegoInitContext + conversationHistory + '@')
                }

                function talk(person, context) {
                    let currentResponsesLength = Math.ceil(Math.random() * config.responsesMaxLength + config.responsesMinLength)

                    $.ajax({
                        url: "https://api.openai.com/v1/chat/completions",
                        method: 'POST',
                        headers: {"Content-Type": "application/json", "Authorization": "Bearer {{env('CHAT_GPT_KEY')}}"},
                        data: JSON.stringify({
                            "model": "gpt-3.5-turbo",
                            "messages": [
                                {
                                    "role": "user",
                                    "content": context + '. Contestale, profundizá. No te despidas.' + '. En menos de ' + currentResponsesLength+ ' palabras.'
                                }
                            ],
                            "temperature": config.temperature,
                            "max_tokens": config.maxTokens
                        })
                    }).done(function (res) {
                        const answer = res.choices[0].message.content
                        updateConversationAndSpeak(answer, person)
                        addDialogToUI(personTalking, answer)
                        runEmotionAnalizer(answer) // necesita correr updateConversationAndSpeak para actualizar lastSaid.
                    });
                }

                function runEmotionAnalizer (textToReact) {
                    const actorListening = getActorListeningByName(personTalking)
                    const gutsContext = buildGutsContext(actorListening, textToReact)
                    console.log('gutsContext', gutsContext, personTalking)

                    $.ajax({
                        url: "https://api.openai.com/v1/chat/completions",
                        method: 'POST',
                        headers: {"Content-Type": "application/json", "Authorization": "Bearer {{env('CHAT_GPT_KEY')}}"},
                        data: JSON.stringify({
                            "model": "gpt-3.5-turbo",
                            "messages": [
                                {
                                    "role": "user",
                                    "content": gutsContext
                                }
                            ],
                            "temperature": config.temperature,
                            "max_tokens": config.maxTokens
                        })
                    }).done(function (res) {
                        const emotion = res.choices[0].message.content
                        actorListening.emotion = emotion
                        console.log('emotion', actorListening.emotion)
                    });
                }
            }
        }
    }

    function formatToDialog(person, text) {
        const emotion = getActorTalkingByName(personTalking).emotion
        return '- ' + person + ': ' + text + ` (${emotion})` + '\n'
    }

    function addDialogToUI(personTalking, lastSaid) {
        const conversation = document.getElementById("conversation");
        var text = document.createTextNode(formatToDialog(personTalking, lastSaid));
        document.body.style = "white-space: pre;"
        conversation.appendChild(text);
    }

    function updateConversationAndSpeak(answer, person) {
        // update ConversationHistory
        conversationHistory = conversationHistory + (personTalking == 'Diego' ? '-Diego dijo: "' + answer + '".' : '-Mónica dijo: "' + answer + '".')

        // update lastSaid
        lastSaid = answer;

        // talk
        person.text = answer
        speechSynthesis.speak(person);
    }

    function buildDiegoContext() {
        return actorDiego.base
            + ' Tu personalidad es: ' + actorDiego.personality.join(', ') + '.'
            + ' La emoción que estas sintiendo es: ' + actorDiego.emotion + '.'
    }

    function buildMonicaContext() {
        return actorMonica.base
            + ' Tu personalidad es: ' + actorMonica.personality.join(', ') + '.'
            + ' La emoción que estas sintiendo es: ' + actorMonica.emotion + '.'
    }

    function buildGutsContext(actorListening, textToReact) {
        if (!textToReact) return
        const personality = actorListening.personality.join(', ') + '.'

        return actorGuts.base
            .replace('{PERSONALITY}', personality)
            .replace('{TEXT_TO_REACT}', textToReact)
    }

    function getActorTalkingByName() {
        return personTalking === 'Diego' ? actorDiego : actorMonica
    }

    function getActorListeningByName() {
        return personTalking === 'Diego' ? actorMonica : actorDiego
    }
</script>
</html>
