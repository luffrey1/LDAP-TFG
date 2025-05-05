# Monitor Agent para Windows
# Este script recopila información del sistema y la envía a un servidor central de monitorización
# Uso: powershell -ExecutionPolicy Bypass -File MonitorAgent.ps1 -ServerUrl "http://servidor:puerto"

param (
    [string]$ServerUrl = "http://localhost:8000",
    [int]$Interval = 300,
    [switch]$RunOnce = $false
)

$ScriptVersion = "1.0.0"
Write-Host "Monitor Agent para Windows v$ScriptVersion"
Write-Host "Servidor: $ServerUrl"
Write-Host "Intervalo: $Interval segundos"
if ($RunOnce) { Write-Host "Modo: Ejecución única" } else { Write-Host "Modo: Ejecución continua" }

function Get-MACAddress {
    try {
        $NetworkAdapter = Get-NetAdapter | Where-Object { $_.Status -eq "Up" } | Select-Object -First 1
        if ($NetworkAdapter) {
            return $NetworkAdapter.MacAddress
        }
        return "00:00:00:00:00:00"
    }
    catch {
        return "00:00:00:00:00:00"
    }
}

function Get-IPAddress {
    try {
        $IPAddress = (Get-NetIPAddress | Where-Object { $_.AddressFamily -eq "IPv4" -and $_.PrefixOrigin -ne "WellKnown" } | Select-Object -First 1).IPAddress
        if ($IPAddress) {
            return $IPAddress
        }
        return "0.0.0.0"
    }
    catch {
        return "0.0.0.0"
    }
}

function Get-CPUUsage {
    try {
        $CpuUsage = (Get-Counter '\Processor(_Total)\% Processor Time').CounterSamples.CookedValue
        return [math]::Round($CpuUsage, 2)
    }
    catch {
        return 0
    }
}

function Get-MemoryInfo {
    try {
        $OS = Get-WmiObject -Class Win32_OperatingSystem
        $TotalMemoryGB = [math]::Round($OS.TotalVisibleMemorySize / 1MB, 2)
        $FreeMemoryGB = [math]::Round($OS.FreePhysicalMemory / 1MB, 2)
        $UsedMemoryGB = [math]::Round($TotalMemoryGB - $FreeMemoryGB, 2)
        $MemoryUsagePercent = [math]::Round(($UsedMemoryGB / $TotalMemoryGB) * 100, 2)
        
        return @{
            TotalGB = $TotalMemoryGB
            UsedGB = $UsedMemoryGB
            FreeGB = $FreeMemoryGB
            UsagePercent = $MemoryUsagePercent
        }
    }
    catch {
        return @{
            TotalGB = 0
            UsedGB = 0
            FreeGB = 0
            UsagePercent = 0
        }
    }
}

function Get-DiskInfo {
    try {
        $Disks = Get-WmiObject -Class Win32_LogicalDisk -Filter "DriveType=3" | ForEach-Object {
            $TotalGB = [math]::Round($_.Size / 1GB, 2)
            $FreeGB = [math]::Round($_.FreeSpace / 1GB, 2)
            $UsedGB = [math]::Round($TotalGB - $FreeGB, 2)
            $UsagePercent = [math]::Round(($UsedGB / $TotalGB) * 100, 2)
            
            @{
                Drive = $_.DeviceID
                TotalGB = $TotalGB
                UsedGB = $UsedGB
                FreeGB = $FreeGB
                UsagePercent = $UsagePercent
            }
        }
        return $Disks
    }
    catch {
        return @(@{
            Drive = "C:"
            TotalGB = 0
            UsedGB = 0
            FreeGB = 0
            UsagePercent = 0
        })
    }
}

function Get-SystemInfo {
    try {
        $ComputerSystem = Get-WmiObject -Class Win32_ComputerSystem
        $OperatingSystem = Get-WmiObject -Class Win32_OperatingSystem
        $Processor = Get-WmiObject -Class Win32_Processor
        $Memory = Get-MemoryInfo
        $Disks = Get-DiskInfo
        $CPUUsage = Get-CPUUsage
        
        $LastBoot = [Management.ManagementDateTimeConverter]::ToDateTime($OperatingSystem.LastBootUpTime)
        $Uptime = (Get-Date) - $LastBoot
        $UptimeString = "{0} días, {1:D2}:{2:D2}:{3:D2}" -f $Uptime.Days, $Uptime.Hours, $Uptime.Minutes, $Uptime.Seconds
        
        $SystemInfo = @{
            hostname = $env:COMPUTERNAME
            ip_address = Get-IPAddress
            mac_address = Get-MACAddress
            os_name = $OperatingSystem.Caption
            os_version = $OperatingSystem.Version
            os_arch = $OperatingSystem.OSArchitecture
            cpu_model = $Processor.Name
            cpu_cores = $Processor.NumberOfCores
            cpu_logical = $Processor.NumberOfLogicalProcessors
            ram_total = $Memory.TotalGB
            ram_used = $Memory.UsedGB
            ram_free = $Memory.FreeGB
            ram_usage = $Memory.UsagePercent
            disks = $Disks
            cpu_usage = $CPUUsage
            last_boot = $LastBoot.ToString("yyyy-MM-dd HH:mm:ss")
            uptime = $UptimeString
            agent_version = $ScriptVersion
        }
        
        return $SystemInfo
    }
    catch {
        Write-Host "Error recopilando información del sistema: $($_.Exception.Message)"
        return $null
    }
}

function Send-SystemInfo {
    try {
        $SystemInfo = Get-SystemInfo
        if ($null -eq $SystemInfo) {
            Write-Host "No se pudo recopilar información del sistema"
            return $false
        }
        
        $JsonData = $SystemInfo | ConvertTo-Json -Depth 10
        $URL = "$ServerUrl/api/telemetry/update"
        
        Write-Host "Enviando datos a $URL"
        
        $Headers = @{
            "Content-Type" = "application/json"
        }
        
        $Response = Invoke-RestMethod -Uri $URL -Method Post -Body $JsonData -Headers $Headers -UseBasicParsing
        
        Write-Host "Respuesta del servidor: $Response"
        return $true
    }
    catch {
        Write-Host "Error enviando información al servidor: $($_.Exception.Message)"
        return $false
    }
}

# Ejecución principal
if ($RunOnce) {
    # Ejecutar una sola vez
    Send-SystemInfo
}
else {
    # Ejecutar en bucle
    Write-Host "Iniciando monitorización. Presiona Ctrl+C para detener."
    try {
        while ($true) {
            $Success = Send-SystemInfo
            if (!$Success) {
                Write-Host "Error en el envío. Reintentando en $Interval segundos."
            }
            Start-Sleep -Seconds $Interval
        }
    }
    catch {
        Write-Host "Monitorización detenida: $($_.Exception.Message)"
    }
} 