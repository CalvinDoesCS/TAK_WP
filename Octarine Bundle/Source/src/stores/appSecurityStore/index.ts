import { create } from "zustand";

export type Actions = {
  setAppSecurity: (props: { isLoggedIn: boolean }) => void;
};

export type AppSecurityStore = {
  appSecurity: {
    isLoggedIn: boolean;
  };
} & Actions;

export const getAppSecurity = (): AppSecurityStore["appSecurity"] => {
  const appSecurity = localStorage.getItem("appSecurity");

  if (appSecurity) {
    return JSON.parse(appSecurity);
  }

  const defaultAppSecurity = {
    isLoggedIn: false,
  };

  localStorage.setItem("appSecurity", JSON.stringify(defaultAppSecurity));

  return defaultAppSecurity;
};

const useAppSecurityStore = create<AppSecurityStore>((set) => ({
  appSecurity: getAppSecurity(),
  setAppSecurity: (properties) => {
    const appSecurity = localStorage.getItem("appSecurity");

    if (appSecurity) {
      localStorage.setItem(
        "appSecurity",
        JSON.stringify({
          ...JSON.parse(appSecurity),
          ...properties,
        })
      );
    }

    set((state) => {
      return {
        ...state,
        appSecurity: {
          ...state.appSecurity,
          ...properties,
        },
      };
    });
  },
}));

export { useAppSecurityStore };
