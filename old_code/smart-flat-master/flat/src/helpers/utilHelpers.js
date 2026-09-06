const buildJsonParams2String = (paramsJson) => {
    let params = '';
    for (let item in paramsJson) {
        params += !params
            ? `${item}=${paramsJson[item]}`
            : `&${item}=${paramsJson[item]}`;
    }
    return params;
};

export default {
    buildJsonParams2String,
};
