const express = require('express');
const fs = require('fs');
const path = require('path');
const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static(__dirname));

const KEYS_FILE = path.join(__dirname, 'keys.json');

app.post('/api/validate-key', (req, res) => {
    const { key, hwid } = req.body;
    
    if (!key || !hwid) {
        return res.status(400).json({ success: false, message: 'Key e HWID são necessários.' });
    }

    const data = JSON.parse(fs.readFileSync(KEYS_FILE, 'utf8'));
    const keyData = data.keys[key];

    if (!keyData) {
        return res.json({ success: false, message: 'Key inválida!' });
    }

    const now = Date.now();

    // Check if key is expired
    if (keyData.expiresAt && now > keyData.expiresAt) {
        return res.json({ success: false, message: 'Esta key expirou e não pode mais ser usada.' });
    }

    if (keyData.hwid === null) {
        // First activation
        keyData.hwid = hwid;
        // Set expiration if not infinite (-1)
        if (keyData.durationDays !== -1) {
            keyData.expiresAt = now + (keyData.durationDays * 24 * 60 * 60 * 1000);
        }
        fs.writeFileSync(KEYS_FILE, JSON.stringify(data, null, 2));
        return res.json({ 
            success: true, 
            message: 'Key ativada com sucesso!', 
            userData: { level: keyData.level, limit: keyData.limit, canCreatePackage: keyData.packages > 0 } 
        });
    } else if (keyData.hwid === hwid) {
        // Already linked to this device
        return res.json({ 
            success: true, 
            message: 'Bem-vindo de volta!', 
            userData: { level: keyData.level, limit: keyData.limit, canCreatePackage: keyData.packages > 0 } 
        });
    } else {
        // HWID mismatch
        return res.json({ success: false, message: 'Esta key já foi usada em outro dispositivo (HWID).' });
    }
});

app.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});
