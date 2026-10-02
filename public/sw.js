/*
 | SEMS service worker — exists so browsers offer "Install app".
 | It deliberately caches nothing and has no fetch handler: every request goes
 | straight to the network, so attendance, payroll and approvals are always live.
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
