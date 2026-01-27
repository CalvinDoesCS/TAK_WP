import { FileActionAnimator } from "@/components/FileSystem/FileActionAnimator";
import FileDragDropHandler from "@/components/FileSystem/FileDragDropHandler";
import CtrlKeyProvider from "../CtrlKeyProvider";
import ClipboardProvider from "../ClipboardProvider";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  return (
    <FileActionAnimator>
      <ClipboardProvider>
        <CtrlKeyProvider>
          <FileDragDropHandler>{children}</FileDragDropHandler>
        </CtrlKeyProvider>
      </ClipboardProvider>
    </FileActionAnimator>
  );
}

export default Main;
