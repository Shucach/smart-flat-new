import { createStore } from 'vuex';
import user from './user.module';
import media from './media.module';
import frame from './frame.module';
import system from './system.module';

export default createStore({
    modules: {
        user,
        media,
        frame,
        system,
    },
});
