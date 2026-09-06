let headers = {
    'Content-Type': 'multipart/form-data',
};

// Auth
const token = localStorage.getItem('token');
if (token) {
    headers['Authorization'] = 'Bearer ' + token;
}

export default headers;
