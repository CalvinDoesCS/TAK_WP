import Highlight from "../../components/Highlight";
import Paragraph from "../../components/Paragraph";
import PreviewCode from "../../components/PreviewCode";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Running the Development Server
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              To start the development server with Vite, simply run:
            </Paragraph>
            <PreviewCode>npm run dev</PreviewCode>
            <Paragraph>
              This will launch the application, which you can access at{" "}
              <Highlight>http://localhost:5173</Highlight>. Vite provides fast
              hot-reloading, so any changes you make will reflect instantly in
              your browser.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
