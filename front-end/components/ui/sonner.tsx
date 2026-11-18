"use client"

import { useTheme } from "next-themes"
import { Toaster as Sonner, type ToasterProps } from "sonner"
import { CheckCircle2, CircleX, InfoIcon, Loader2Icon, TriangleAlert } from "lucide-react"

const Toaster = ({ ...props }: ToasterProps) => {
  const { theme = "system" } = useTheme()

  return (
    <Sonner
      theme={theme as ToasterProps["theme"]}
      className="toaster group"
      richColors
      closeButton={false}
      icons={{
        success: <CheckCircle2 className="size-6 text-white" />,
        info: <InfoIcon className="size-6 text-white" />,
        warning: <TriangleAlert className="size-6 text-white" />,
        error: <CircleX className="size-6 text-white" />,
        loading: <Loader2Icon className="size-6 animate-spin text-white" />,
      }}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as React.CSSProperties
      }
      toastOptions={{
        duration: 5000,
        classNames: {
          toast: "cn-toast relative min-w-[min(380px,calc(100vw-2rem))] overflow-hidden !border-2 !p-5 !text-base !font-semibold !text-white !shadow-2xl animate-in slide-in-from-right-full duration-300",
          title: "!font-bold !text-white",
          description: "!text-white",
          success: "!border-2 !border-emerald-300 !bg-emerald-600 !text-white",
          error: "!border-2 !border-rose-300 !bg-rose-600 !text-white",
          warning: "!border-2 !border-amber-300 !bg-amber-600 !text-white",
          info: "!border-2 !border-sky-300 !bg-sky-700 !text-white",
          default: "!border-2 !border-slate-300 !bg-slate-700 !text-white",
          loading: "!border-2 !border-slate-300 !bg-slate-700 !text-white",
        },
      }}
      {...props}
    />
  )
}

export { Toaster }
