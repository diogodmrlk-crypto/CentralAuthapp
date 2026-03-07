const express = require('express');
const fs = require('fs');
const path = require('path');
const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static(__dirname));

const KEYS_FILE = path.join(__dirname, 'keys.json');

// Initialize keys.json if it doesn't exist
if (!fs.existsSync(KEYS_FILE)) {
    const initialKeys = {};
    for (let i = 1; i <= 20; i++) {
        initialKeys[`FERRAODEV${i}`] = null;
    }
    fs.writeFileSync(KEYS_FILE, JSON.stringify({ keys: initialKeys }, null, 2));
}

app.post('/api/validate-key', (req, res) => {
    const { key, hwid } = req.body;
    
    if (!key || !hwid) {
        return res.status(400).json({ success: false, message: 'Key e HWID são necessários.' });
    }

    const data = JSON.parse(fs.readFileSync(KEYS_FILE, 'utf8'));
    
    if (!(key in data.keys)) {
        return res.json({ success: false, message: 'Key inválida!' });
    }

    const storedHwid = data.keys[key];

    if (storedHwid === null) {
        // First time using the key, link it to this HWID
        data.keys[key] = hwid;
        fs.writeFileSync(KEYS_FILE, JSON.stringify(data, null, 2));
        return res.json({ success: true, message: 'Key ativada com sucesso!' });
    } else if (storedHwid === hwid) {
        // Key already linked to this HWID
        return res.json({ success: true, message: 'Bem-vindo de volta!' });
    } else {
        // Key used on another HWID
        return res.json({ success: false, message: 'Esta key já foi usada em outro dispositivo (HWID).' });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});
