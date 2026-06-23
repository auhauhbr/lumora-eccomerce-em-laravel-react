import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Aplicacao } from './aplicacao/Aplicacao';
import '../css/app.css';

createRoot(document.getElementById('aplicacao')!).render(
    <React.StrictMode>
        <BrowserRouter>
            <Aplicacao />
        </BrowserRouter>
    </React.StrictMode>,
);
