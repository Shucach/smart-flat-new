import axios from 'axios';
import headers from './header-rules';

const API_URL = import.meta.env.VITE_APP_API_URL;

class Api {
    __GET(url, params) {
        const URL = params
            ? url + '?' + this.#buildJsonParams2String(params)
            : url;
        return axios
            .get(API_URL + URL, {
                headers: headers,
            })
            .then((response) => {
                return response;
            })
            .catch((error) => {
                return Promise.reject(error);
            });
    }

    __POST(url, params) {
        return axios
            .post(API_URL + url, params, {
                headers: headers,
            })
            .then((response) => {
                return response;
            })
            .catch((error) => {
                return Promise.reject(error);
            });
    }

    #buildJsonParams2String(paramsJson) {
        let params = '';
        for (let item in paramsJson) {
            if (Array.isArray(paramsJson[item])) {
                for (let i = 0; i < paramsJson[item].length; i++) {
                    let res = paramsJson[item][i];
                    params += !params
                        ? `${item}[]=${res}`
                        : `&${item}[]=${res}`;
                }
            } else {
                params += !params
                    ? `${item}=${paramsJson[item]}`
                    : `&${item}=${paramsJson[item]}`;
            }
        }
        return params;
    }
}

export default new Api();
