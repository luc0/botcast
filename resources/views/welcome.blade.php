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
    const config = {
        responsesMaxLength: 8,
        responsesMinLength: 1,
        temperature: 0.7,
        maxTokens: 2048
    }

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


    const diegoInitContext = `Vos sos Diego y estas hablando con Mónica. Tu objetivo es generar conversación, aveces preguntando, contando algo, preguntando algo muy especifico o inesperado, rara vez tirar un chiste.

        `
    // Siempre al final de tu dialogo entre parentesis mencioná cuál es la última palabra exacta que dijo mónica, si es que dijo alguna.
    // SIEMPRE Al final de tu dialogo, valora del 1 al 10 que tan agresiva crees que fue mónica en su último dialogo, y aclará que palabra utilizó la cual suena agresiva y porque.
    //     Tu personalidad es:
    //         directo, haces humor inteligente,
    //         el 50% de las veces sos sarcastico,
    //         el 30% de las veces transmitis una opinion controvertida
    //         aveces estas de acuerdo con lo que dice Mónica, otras veces dudas y otras veces estas en desacuerdo.
    // `
    const monicaInitContext = `Vos sos Mónica y estas hablando con Diego. Tu objetivo es generar conversación. aveces preguntando, contando algo, preguntando algo muy especifico o inesperado, rara vez tirar un chiste.
        Tu personalidad es:
            hablas con lunfardo, no tenes un nivel alto de educación,
            sos grosera, dudas mucho de lo que decis
            le gusta usar analogias para explicar,
            siempre esta en desacuerdo con Diego.
            No cree nada de lo que dice Diego.
    `

    let conversationHistory = ''
    let personTalking = 'Diego'
    // const initContext = 'Simulá que sos un locutor de un podcast y dale la bienvenida a los oyentes en una oración. Son 2 personas vos sos Diego y la otra persona es Mónica.'
    // const initContext = 'Son 2 personas hablando vos sos Diego y la otra persona es Mónica.'

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
                let lastSaid = initContext + diegoInitContext
                conversationHistory = 'Teniendo como contexto que hubo una conversación previa entre Diego y Mónica, la cual pondré un signo @ en donde termina, no se debe mencionar: '

                talk(diegoVoice, lastSaid + '')

                diegoVoice.onend = function () {
                    personTalking = 'Mónica'
                    console.log('talk instruction:', monicaInitContext + conversationHistory + '. FIN')
                    talk(monicaVoice, monicaInitContext + conversationHistory + '@')
                }

                monicaVoice.onend = function () {
                    personTalking = 'Diego'
                    console.log('talk instruction:', diegoInitContext + conversationHistory + '. FIN')
                    talk(diegoVoice, diegoInitContext + conversationHistory + '@')
                }

                function talk(person, context) {
                    let currentResponsesLength = Math.ceil(Math.random() * config.responsesMaxLength + config.responsesMinLength)
                    console.log('currentResponsesLength', currentResponsesLength)
                    console.log('conversacion:', conversationHistory)

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
                    });
                }
            }
        }
    }

    function formatToDialog(person, text) {
        return '- ' + person + ': ' + text + '\n'
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
        person.text = answer;
        speechSynthesis.speak(person);
    }
</script>
</html>
