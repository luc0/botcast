Próxima versión:

**Objetivo**: 
- Que una persona reaccione de otra forma dependiendo un contexto. Ej: Mónica es agresiva y Diego se enoja aunque el no es asi normalmente.

**Problema:**
- Quise usar probabilidades pero chatgpt no entiende probabilidades, entonces no puedo decir que Diego es cruel el 10% de las veces, etc.
- No puedo en la personalidad de Diego decirle que analice la conversación y vea si mónica esta siendo agresiva, porque empieza a confundir y mezclar todo.
    Empieza a hablar como si fuera mónica, confunde el historico de conversación de él y piensa que lo dijo mónica, etc.

**Solución:**
- Los contextos tienen que estar encapsulados. Y la finalidad debe ser única.
    Diego es una persona que habla con Mónica con x personalidad. FIN. No debe hacer otra cosa.
- Para que una persona sea consciente del dialogo que van llevando y actue de X manera, tengo que hacer un análisis por separado.
    podría crear un personaje "analizador", el cual analiza lo que dice cada uno y modifica las emociones de los interlocutores.
- Esto se resume en introducir ACTORES.
  - ACTOR 1: 'Monica'. Objetivo: charlar. | Personalidad: A.
  - ACTOR 2: 'Diego'. Objetivo: charlar. | Personalidad: B.
  - ACTOR 3: 'Analizador'. Objetivo: cambiar emociones de Actor 1 y Actor 2. Básicamente cambia el texto de personalidad.

