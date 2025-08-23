// @ts-nocheck
let storage = {
  getAccessToken: () => localStorage.getItem('access_token'),
  setAccessToken: (token) =>
    token ? localStorage.setItem('access_token', token) : localStorage.removeItem('access_token'),
  getRefreshToken: () => localStorage.getItem('refresh_token'),
  setRefreshToken: (token) =>
    token ? localStorage.setItem('refresh_token', token) : localStorage.removeItem('refresh_token'),
};

export function injectStorage(custom) {
  storage = { ...storage, ...custom };
}

export function getAccessToken() {
  return storage.getAccessToken() || '';
}

export function getRefreshToken() {
  return storage.getRefreshToken() || '';
}

export function setTokens(access, refresh) {
  storage.setAccessToken(access);
  storage.setRefreshToken(refresh);
}

export function clearTokens() {
  storage.setAccessToken(null);
  storage.setRefreshToken(null);
}
