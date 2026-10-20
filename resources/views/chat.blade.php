

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ToDo AI Chat</title>

    <style>
        body {
            font-family: sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 16px;
        }

        #messages {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            min-height: 300px;
            margin-bottom: 16px;
        }

        .message {
            margin-bottom: 16px;
            white-space: pre-wrap;
        }

        .user {
            color: #155eef;
        }

        .assistant {
            color: #222;
        }

        form {
            display: flex;
            gap: 8px;
        }

        input {
            flex: 1;
            padding: 12px;
        }

        button {
            padding: 12px 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>ToDo AI Chat</h1>

    <div id="messages"></div>

    <form id="chat-form">
        <input
            id="message"
            type="text"
            placeholder="Напиши повідомлення..."
            autocomplete="off"
            required
        >
        <button id="send" type="submit">Надіслати</button>
    </form>

    <script>
        
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const sendButton = document.getElementById('send');
const messagesContainer = document.getElementById('messages');

const messages = [];

function renderMessage(role, text) {
    const element = document.createElement('div');
    element.className = `message ${role}`;
    element.textContent =
        `${role === 'user' ? 'Ти' : 'AI'}: ${text}`;

    messagesContainer.appendChild(element);
    return element;
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const text = input.value.trim();

    if (!text) {
        return;
    }

    messages.push({
        id: crypto.randomUUID(),
        role: 'user',
        parts: [{ type: 'text', text }],
    });

    renderMessage('user', text);

    input.value = '';
    input.disabled = true;
    sendButton.disabled = true;

    const assistantElement = renderMessage('assistant', '');
    let assistantText = '';

    try {
        const response = await fetch('/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .content,
            },
            body: JSON.stringify({ messages }),
        });

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}: ${await response.text()}`
            );
        }

        if (!response.body) {
            throw new Error('Браузер не отримав потік відповіді.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { value, done } = await reader.read();

            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });

            const events = buffer.split('\n\n');
            buffer = events.pop() ?? '';

            for (const eventText of events) {
                const dataLines = eventText
                    .split('\n')
                    .filter(line => line.startsWith('data:'))
                    .map(line => line.slice(5).trimStart());

                if (!dataLines.length) {
                    continue;
                }

                const data = dataLines.join('\n');

                if (data === '[DONE]') {
                    continue;
                }

                let part;

                try {
                    part = JSON.parse(data);
                } catch {
                    continue;
                }

                if (part.type === 'text-delta') {
                    assistantText += part.delta;
                    assistantElement.textContent = `AI: ${assistantText}`;
                }
            }
        }

        messages.push({
            id: crypto.randomUUID(),
            role: 'assistant',
            parts: [{ type: 'text', text: assistantText }],
        });

    } catch (error) {
        assistantElement.textContent = `Помилка: ${error.message}`;
        console.error(error);
    } finally {
        input.disabled = false;
        sendButton.disabled = false;
        input.focus();
    }
});

    </script>
</body>
</html>
