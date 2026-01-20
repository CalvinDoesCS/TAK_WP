import "./assets/css/app.css";
import Desktop from "@/components/Desktop";
import MenuBar from "@/components/MenuBar";
import Dock from "@/components/Dock";
import Notification from "@/components/Notification";
import ApplicationLoader from "@/components/ApplicationLoader";
import { FileSystem } from "@/components/FileSystem";
import RightClickMenu from "@/components/RightClickMenu";
import Wallpaper from "@/components/Wallpaper";
import MobileMenu from "@/components/MobileMenu";
import LockScreen from "@/components/LockScreen";
import { useAppSecurityStore } from "@/stores/appSecurityStore";

function App() {
  const appSecurityStore = useAppSecurityStore();

  return (
    <div className="relative w-screen overflow-hidden text-sm h-svh scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full scrollbar-thumb-muted-foreground/30 scrollbar-track-transparent">
      {!appSecurityStore.appSecurity.isLoggedIn ? (
        <>
          <Wallpaper showTime={false} />
          <LockScreen />
        </>
      ) : (
        <RightClickMenu>
          <>
            <Wallpaper />
            <FileSystem>
              <MenuBar />
              <Desktop />
              <Notification />
              <ApplicationLoader />
              <Dock />
              <MobileMenu />
            </FileSystem>
          </>
        </RightClickMenu>
      )}
    </div>
  );
}

export default App;
